/*
 * The install offer (FR-54, UX-DR26): offered once, from the second visit, in
 * a browser where the app is not installed. On Android it opens the browser's
 * own prompt; on an iPhone it links to the Install page. Dismissing it ends
 * the offer. The state lives in localStorage; if that cannot be read, there
 * is no offer at all.
 */

const VISITS_KEY = 'integrator.install.visits';
const DISMISSED_KEY = 'integrator.install.dismissed';
const OFFERED_KEY = 'integrator.install.offered';
/** One visit is one browsing session: counted once per tab session, not per page. */
const COUNTED_KEY = 'integrator.install.visit-counted';

/** Chrome's `beforeinstallprompt` event, which the DOM types do not carry. */
export interface InstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

export type OfferKind = 'android' | 'iphone';

export interface InstallEnvironment {
    /** localStorage, or null when it cannot be used. */
    storage: Storage | null;
    /** sessionStorage, or null when it cannot be used. */
    session: Storage | null;
    /** Opened from the home screen already. */
    standalone: boolean;
    /** iPhone or iPad, where installing is Share, then Add to Home Screen. */
    ios: boolean;
}

function read(storage: Storage, key: string): string | null {
    return storage.getItem(key);
}

/**
 * Counts this visit (once per browsing session) and says whether the offer
 * may be made now. Any storage failure means no offer.
 */
export function startVisit(env: InstallEnvironment): boolean {
    // Without session storage every page load would count as a visit, so there is no offer.
    if (env.standalone || env.storage === null || env.session === null) {
        return false;
    }

    try {
        const storage = env.storage;

        if (env.session.getItem(COUNTED_KEY) !== '1') {
            const visits = Number.parseInt(read(storage, VISITS_KEY) ?? '0', 10);
            storage.setItem(VISITS_KEY, String((Number.isFinite(visits) ? visits : 0) + 1));
            env.session.setItem(COUNTED_KEY, '1');
        }

        return eligible(env);
    } catch {
        return false;
    }
}

/** Second visit or later, never dismissed, never offered in an earlier visit. */
export function eligible(env: InstallEnvironment): boolean {
    if (env.standalone || env.storage === null || env.session === null) {
        return false;
    }

    try {
        const visits = Number.parseInt(read(env.storage, VISITS_KEY) ?? '0', 10);
        const dismissed = read(env.storage, DISMISSED_KEY) === '1';
        const offeredBefore = read(env.storage, OFFERED_KEY);
        const offeredThisVisit = offeredBefore !== null && offeredBefore === env.session?.getItem(OFFERED_KEY);

        return visits >= 2 && !dismissed && (offeredBefore === null || offeredThisVisit);
    } catch {
        return false;
    }
}

/** Records that the offer has been shown, so a later visit does not show it again. */
export function markOffered(env: InstallEnvironment): void {
    try {
        if (env.storage === null || env.storage.getItem(OFFERED_KEY) !== null) {
            return;
        }

        const stamp = String(Date.now());
        env.storage.setItem(OFFERED_KEY, stamp);
        env.session?.setItem(OFFERED_KEY, stamp);
    } catch {
        // The offer still ends when dismissed; nothing else to do.
    }
}

/** Ends the offer on this browser. The Install page still works. */
export function dismiss(env: InstallEnvironment): void {
    try {
        env.storage?.setItem(DISMISSED_KEY, '1');
    } catch {
        // Nothing to do: the offer is hidden for this visit either way.
    }
}

/** Which offer fits this browser: the browser's prompt, the iPhone steps, or none yet. */
export function offerKind(env: InstallEnvironment, prompt: InstallPromptEvent | null): OfferKind | null {
    if (env.ios) {
        return 'iphone';
    }

    return prompt === null ? null : 'android';
}

function safeStorage(get: () => Storage): Storage | null {
    try {
        const storage = get();
        const probe = 'integrator.install.probe';
        storage.setItem(probe, '1');
        storage.removeItem(probe);

        return storage;
    } catch {
        return null;
    }
}

export function browserEnvironment(win: Window = window): InstallEnvironment {
    const nav = win.navigator as Navigator & { standalone?: boolean };
    const standalone =
        nav.standalone === true ||
        (typeof win.matchMedia === 'function' && win.matchMedia('(display-mode: standalone)').matches);
    const ios = /iPhone|iPad|iPod/.test(nav.userAgent) || (nav.platform === 'MacIntel' && nav.maxTouchPoints > 1);

    return {
        storage: safeStorage(() => win.localStorage),
        session: safeStorage(() => win.sessionStorage),
        standalone,
        ios,
    };
}

type Listener = () => void;

/**
 * Holds Chrome's install prompt, which can fire before React mounts, so it is
 * listened for from the first line of the app.
 */
export function createPromptSource(win: Window = window) {
    let deferred: InstallPromptEvent | null = null;
    let installed = false;
    const listeners = new Set<Listener>();
    const notify = () => listeners.forEach((listener) => listener());

    win.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferred = event as InstallPromptEvent;
        notify();
    });
    win.addEventListener('appinstalled', () => {
        deferred = null;
        installed = true;
        notify();
    });

    return {
        subscribe(listener: Listener): () => void {
            listeners.add(listener);

            return () => listeners.delete(listener);
        },
        current: (): InstallPromptEvent | null => deferred,
        installed: (): boolean => installed,
        /** Opens the browser's own prompt; a prompt can be used once. */
        async open(): Promise<'accepted' | 'dismissed' | 'unavailable'> {
            const event = deferred;

            if (event === null) {
                return 'unavailable';
            }

            deferred = null;
            notify();

            try {
                await event.prompt();
                const choice = await event.userChoice;

                return choice.outcome;
            } catch {
                // The browser refused or lost the prompt; the Install page steps still work.
                return 'unavailable';
            }
        },
    };
}

export type PromptSource = ReturnType<typeof createPromptSource>;
