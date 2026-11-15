/*
 * Keeps what a person typed across an update's reload: every named or
 * labelled text field, choice and select on the page goes into this tab's
 * sessionStorage, and is put back after the new version loads. Passwords,
 * one-time codes, card fields and fields marked data-no-draft are never kept.
 */

const KEY = 'integrator.update-draft';
const SKIPPED_TYPES = new Set(['password', 'hidden', 'file', 'submit', 'button', 'reset', 'image']);
const SKIPPED_AUTOCOMPLETE = new Set(['one-time-code', 'current-password', 'new-password']);

type Field = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;
type Draft = { path: string; values: Record<string, string | boolean> };

function secret(field: Field): boolean {
    const tokens = (field.getAttribute('autocomplete') ?? '').toLowerCase().split(/[ \t\n\r\f]+/);

    return tokens.some((token) => SKIPPED_AUTOCOMPLETE.has(token) || token.startsWith('cc-'));
}

function fieldKey(field: Field): string | null {
    if (field.dataset.noDraft !== undefined || secret(field)) {
        return null;
    }

    if (field instanceof HTMLInputElement && SKIPPED_TYPES.has(field.type)) {
        return null;
    }

    const name = field.name || field.id;

    if (name === '') {
        return null;
    }

    return isChoice(field) ? `${name}=${field.value}` : name;
}

function fields(root: ParentNode): Field[] {
    return Array.from(root.querySelectorAll<Field>('input, textarea, select'));
}

function isChoice(field: Field): field is HTMLInputElement {
    return field instanceof HTMLInputElement && (field.type === 'radio' || field.type === 'checkbox');
}

function isDraft(value: unknown): value is Draft {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const { path, values } = value as Partial<Draft>;

    return (
        typeof path === 'string' &&
        typeof values === 'object' &&
        values !== null &&
        !Array.isArray(values) &&
        Object.values(values).every((entry) => typeof entry === 'string' || typeof entry === 'boolean')
    );
}

/** Sets a value the way typing does, so React-controlled fields see it too. */
function setValue(field: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement, value: string): void {
    const prototype = Object.getPrototypeOf(field) as object;
    const setter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;

    if (setter === undefined) {
        field.value = value;
    } else {
        setter.call(field, value);
    }

    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

export function saveDraft(
    root: ParentNode = document,
    storage: Storage = window.sessionStorage,
    path = location.pathname,
): void {
    const values: Record<string, string | boolean> = {};

    for (const field of fields(root)) {
        const key = fieldKey(field);

        if (key !== null) {
            values[key] = isChoice(field) ? field.checked : field.value;
        }
    }

    try {
        storage.setItem(KEY, JSON.stringify({ path, values } satisfies Draft));
    } catch {
        // A full or blocked storage loses the draft, never the update.
    }
}

/** Forgets a kept draft, say because the update it was kept for did not happen. */
export function clearDraft(storage: Storage = window.sessionStorage): void {
    try {
        storage.removeItem(KEY);
    } catch {
        // Nothing to do.
    }
}

/**
 * Puts the kept values back if this is the page they came from. The draft is
 * forgotten once a field has been filled, or when it belongs to another page;
 * a draft that cannot be read counts as none. Returns how many were filled.
 */
export function restoreDraft(
    root: ParentNode = document,
    storage: Storage = window.sessionStorage,
    path = location.pathname,
): number {
    let draft: unknown;

    try {
        const raw = storage.getItem(KEY);
        draft = raw === null ? null : JSON.parse(raw);
    } catch {
        draft = undefined;
    }

    if (draft === null) {
        return 0;
    }

    if (!isDraft(draft) || draft.path !== path) {
        clearDraft(storage);

        return 0;
    }

    let restored = 0;

    for (const field of fields(root)) {
        const key = fieldKey(field);
        const value = key === null ? undefined : draft.values[key];

        if (value === undefined) {
            continue;
        }

        if (isChoice(field)) {
            // A click checks or unchecks the way a person would, with the events React listens for.
            if (field.checked !== (value === true) && (field.type === 'checkbox' || value === true)) {
                field.click();
            }
        } else {
            setValue(field, String(value));
        }

        restored++;
    }

    if (restored > 0) {
        clearDraft(storage);
    }

    return restored;
}
