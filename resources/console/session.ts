/*
 * The staff console's 12-hour session, in the browser. The server
 * (EndStaffSessionAfter12Hours) is the authority; this script:
 *  - keeps the West Africa Time clock in the top bar current,
 *  - shows the warning 5 minutes before the end, with "Stay signed in",
 *    which asks for the authenticator code again and starts a fresh 12 hours,
 *  - at the end, keeps unsaved form values as a draft in this tab and reloads,
 *    so the server signs the person out and brings them back here after
 *    they sign in again, when the draft is offered back.
 */

export type Draft = Record<string, string | boolean>;

const DRAFT_PREFIX = 'console-draft:';

const SKIPPED_TYPES = new Set(['password', 'hidden', 'file', 'submit', 'button', 'reset', 'image']);

type Field = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

function fieldKey(field: Field): string | null {
    return field.id || field.name || null;
}

function isDraftable(field: Field): boolean {
    if (field.closest('[data-no-draft]') || field.hasAttribute('data-no-draft') || field.disabled) {
        return false;
    }

    if (field instanceof HTMLInputElement && SKIPPED_TYPES.has(field.type)) {
        return false;
    }

    const hint = `${field.name} ${field.id} ${field.getAttribute('autocomplete') ?? ''}`.toLowerCase();

    return !hint.includes('password') && !hint.includes('one-time-code') && !hint.includes('recovery');
}

/** Values a person has typed or changed and not yet saved. */
export function collectDraft(root: ParentNode): Draft {
    const draft: Draft = {};

    root.querySelectorAll<Field>('input, textarea, select').forEach((field) => {
        const key = fieldKey(field);

        if (key === null || !isDraftable(field)) {
            return;
        }

        if (field instanceof HTMLInputElement && (field.type === 'checkbox' || field.type === 'radio')) {
            if (field.checked !== field.defaultChecked) {
                draft[key] = field.checked;
            }

            return;
        }

        if (field instanceof HTMLSelectElement) {
            const changed = Array.from(field.options).some((option) => option.selected !== option.defaultSelected);

            if (changed) {
                draft[key] = field.value;
            }

            return;
        }

        if (field.value !== field.defaultValue) {
            draft[key] = field.value;
        }
    });

    return draft;
}

/** Puts draft values back and tells Livewire, which listens for input events. */
export function applyDraft(root: ParentNode, draft: Draft): number {
    let restored = 0;

    for (const [key, value] of Object.entries(draft)) {
        const field =
            root.querySelector<Field>(`#${CSS.escape(key)}`) ??
            root.querySelector<Field>(`[name="${CSS.escape(key)}"]`);

        if (field === null || !isDraftable(field)) {
            continue;
        }

        if (field instanceof HTMLInputElement && typeof value === 'boolean') {
            field.checked = value;
        } else {
            field.value = String(value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        restored++;
    }

    return restored;
}

/** Drafts belong to one staff member and one page, in this tab only. */
export function draftKey(member: string, path: string): string {
    return `${DRAFT_PREFIX}${member}:${path}`;
}

export function saveDraft(storage: Storage | null, member: string, path: string, draft: Draft): void {
    if (storage === null || member === '' || Object.keys(draft).length === 0) {
        return;
    }

    try {
        storage.setItem(draftKey(member, path), JSON.stringify(draft));
    } catch {
        // Storage can be full or blocked; the sign-out still goes ahead.
    }
}

export function loadDraft(storage: Storage | null, member: string, path: string): Draft | null {
    if (storage === null || member === '') {
        return null;
    }

    try {
        const raw = storage.getItem(draftKey(member, path));
        const parsed: unknown = raw === null ? null : JSON.parse(raw);

        return parsed !== null && typeof parsed === 'object' ? (parsed as Draft) : null;
    } catch {
        return null;
    }
}

export function forgetDraft(storage: Storage | null, member: string, path: string): void {
    try {
        storage?.removeItem(draftKey(member, path));
    } catch {
        // Nothing to do.
    }
}

/** Removes drafts another staff member left in this tab, so they are never offered to this one. */
export function dropOtherMembersDrafts(storage: Storage | null, member: string): void {
    if (storage === null) {
        return;
    }

    try {
        const own = `${DRAFT_PREFIX}${member}:`;
        const others: string[] = [];

        for (let i = 0; i < storage.length; i++) {
            const key = storage.key(i);

            if (key !== null && key.startsWith(DRAFT_PREFIX) && !key.startsWith(own)) {
                others.push(key);
            }
        }

        others.forEach((key) => storage.removeItem(key));
    } catch {
        // Nothing to do.
    }
}

const watFormat = new Intl.DateTimeFormat('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Africa/Lagos',
});

/** "14:05 WAT" */
export function formatWat(instant: Date): string {
    return `${watFormat.format(instant)} WAT`;
}

export function minutesLeftText(secondsLeft: number): string {
    const minutes = Math.max(1, Math.ceil(secondsLeft / 60));

    return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`;
}

function tabStorage(): Storage | null {
    try {
        return window.sessionStorage;
    } catch {
        return null;
    }
}

interface LivewireErrorContext {
    response: Response;
    preventDefault: () => void;
}

interface LivewireGlobal {
    interceptRequest?: (
        callback: (context: { onError: (handler: (context: LivewireErrorContext) => void) => void }) => void,
    ) => void;
}

declare global {
    interface Window {
        Livewire?: LivewireGlobal;
    }
}

interface ExtendReply {
    message?: string;
    ends_at?: string;
    ends_at_wat?: string;
}

interface EndsAtMessage {
    member: string;
    endsAt: string;
    endsAtWat?: string;
}

const CHANNEL = 'console-session';

/** Wires up the console page; returns a function that stops its timer and listeners. */
export function start(doc: Document = document, reload: () => void = () => window.location.reload()): () => void {
    const storage = tabStorage();
    const path = window.location.pathname;
    const pageRoot = doc.getElementById('fi-main-content') ?? doc.body;
    const root = doc.getElementById('console-session');
    const clock = doc.querySelector<HTMLTimeElement>('[data-console-clock]');
    const member = root?.dataset.member ?? '';
    let ended = false;

    const endSession = (): void => {
        if (ended) {
            return;
        }

        ended = true;
        saveDraft(storage, member, path, collectDraft(pageRoot));
        reload();
    };

    const registerLivewire = (): void => {
        window.Livewire?.interceptRequest?.(({ onError }) => {
            onError(({ response, preventDefault }) => {
                if (response.status === 401 && response.headers.get('X-Staff-Session') === 'ended') {
                    preventDefault();
                    endSession();
                }
            });
        });
    };

    if (window.Livewire) {
        registerLivewire();
    } else {
        doc.addEventListener('livewire:init', registerLivewire, { once: true });
    }

    if (root === null) {
        return () => doc.removeEventListener('livewire:init', registerLivewire);
    }

    dropOtherMembersDrafts(storage, member);

    const serverTime = Date.parse(root.dataset.serverTime ?? '');
    const offsetMs = Number.isNaN(serverTime) ? 0 : serverTime - Date.now();

    let endsAt = Date.parse(root.dataset.endsAt ?? '');
    const warningSeconds = Number(root.dataset.warningSeconds ?? '300');
    const warning = root.querySelector<HTMLElement>('[data-session-warning]');
    const minutes = root.querySelector<HTMLElement>('[data-session-minutes]');
    const endsWat = root.querySelector<HTMLElement>('[data-session-ends-wat]');
    const dialog = root.querySelector<HTMLDialogElement>('[data-session-dialog]');
    const form = root.querySelector<HTMLFormElement>('[data-session-form]');
    const codeInput = root.querySelector<HTMLInputElement>('#console-stay-code');
    const error = root.querySelector<HTMLElement>('[data-session-error]');
    const draftBanner = root.querySelector<HTMLElement>('[data-draft-banner]');
    const channel = typeof BroadcastChannel === 'undefined' ? null : new BroadcastChannel(CHANNEL);
    let opener: HTMLElement | null = null;

    const now = (): number => Date.now() + offsetMs;

    const tick = (): void => {
        if (clock !== null) {
            const text = formatWat(new Date(now()));
            clock.textContent = text;
            clock.setAttribute('aria-label', `Time now, West Africa Time: ${text}`);
        }

        const secondsLeft = Math.floor((endsAt - now()) / 1000);

        if (secondsLeft <= 0) {
            endSession();

            return;
        }

        if (warning !== null) {
            const inWarning = secondsLeft <= warningSeconds;

            if (inWarning && minutes !== null) {
                minutes.textContent = minutesLeftText(secondsLeft);
            }

            warning.hidden = !inWarning;
        }
    };

    const useEndsAt = (iso: string, wat?: string): void => {
        const parsed = Date.parse(iso);

        if (Number.isNaN(parsed)) {
            return;
        }

        endsAt = parsed;

        if (endsWat !== null && wat !== undefined) {
            endsWat.textContent = wat;
        }

        tick();
    };

    // Staying signed in from another tab moves the end time here too.
    channel?.addEventListener('message', (event: MessageEvent<EndsAtMessage>) => {
        if (event.data?.member === member) {
            useEndsAt(event.data.endsAt, event.data.endsAtWat);
        }
    });

    const closeDialog = (): void => {
        if (dialog?.open) {
            dialog.close();
        }

        opener?.focus();
    };

    root.querySelector('[data-session-stay]')?.addEventListener('click', (event) => {
        opener = event.currentTarget instanceof HTMLElement ? event.currentTarget : null;

        if (error !== null) {
            error.textContent = '';
        }

        codeInput?.removeAttribute('aria-invalid');

        if (codeInput !== null) {
            codeInput.value = '';
        }

        if (dialog !== null && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }

        codeInput?.focus();
    });

    root.querySelector('[data-session-cancel]')?.addEventListener('click', closeDialog);

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const showError = (message: string): void => {
            if (error !== null) {
                error.textContent = message;
            }

            codeInput?.setAttribute('aria-invalid', 'true');
            codeInput?.focus();
        };

        const code = (codeInput?.value ?? '').replace(/\s+/g, '');

        if (!/^\d{6}$/.test(code)) {
            showError('Enter the 6-digit code from your authenticator app.');

            return;
        }

        const token = doc.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        let response: Response;

        try {
            response = await fetch(root.dataset.extendUrl ?? '', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ code }),
            });
        } catch {
            showError('The console could not be reached. Check your connection and try again.');

            return;
        }

        // 401: the session has ended. 419: its token ended with it.
        if (response.status === 401 || response.status === 419) {
            endSession();

            return;
        }

        let body: ExtendReply = {};

        if ((response.headers.get('Content-Type') ?? '').includes('application/json')) {
            try {
                body = (await response.json()) as ExtendReply;
            } catch {
                body = {};
            }
        }

        if (!response.ok || body.ends_at === undefined) {
            showError(body.message ?? 'That did not work. Try again in a moment.');

            return;
        }

        useEndsAt(body.ends_at, body.ends_at_wat);
        channel?.postMessage({ member, endsAt: body.ends_at, endsAtWat: body.ends_at_wat } satisfies EndsAtMessage);
        closeDialog();
    });

    const draft = loadDraft(storage, member, path);

    if (draft !== null && draftBanner !== null) {
        draftBanner.hidden = false;

        draftBanner.querySelector('[data-draft-restore]')?.addEventListener('click', () => {
            applyDraft(pageRoot, draft);
            forgetDraft(storage, member, path);
            draftBanner.hidden = true;
        });

        draftBanner.querySelector('[data-draft-discard]')?.addEventListener('click', () => {
            forgetDraft(storage, member, path);
            draftBanner.hidden = true;
        });
    }

    tick();
    const interval = window.setInterval(tick, 15_000);

    return () => {
        window.clearInterval(interval);
        channel?.close();
        doc.removeEventListener('livewire:init', registerLivewire);
    };
}

// Vitest drives start() itself; the console page starts it on load.
if (typeof document !== 'undefined' && !import.meta.env.VITEST) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => start(), { once: true });
    } else {
        start();
    }
}
