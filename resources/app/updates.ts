/*
 * A new version waits (UX-DR24). The service worker fetches a new build
 * quietly and holds it; the app never reloads by itself. The "Update when you
 * finish" banner applies it only when the person chooses, and what they typed
 * is kept across the reload (drafts.ts).
 */

type Listener = () => void;

export function createUpdates() {
    let apply: (() => Promise<void>) | null = null;
    const listeners = new Set<Listener>();

    return {
        subscribe(listener: Listener): () => void {
            listeners.add(listener);

            return () => listeners.delete(listener);
        },
        /** True once a new version is installed and waiting. */
        waiting: (): boolean => apply !== null,
        /** The service worker has a new version; `activate` swaps it in and reloads. */
        found(activate: () => Promise<void>): void {
            apply = activate;
            listeners.forEach((listener) => listener());
        },
        async apply(): Promise<void> {
            await apply?.();
        },
    };
}

export type Updates = ReturnType<typeof createUpdates>;

/** The one store the shell uses; tests make their own. */
export const updates = createUpdates();

const HOUR = 60 * 60 * 1000;

/** Registers the service worker (production builds only) and listens for a new version. */
export async function registerServiceWorker(store: Updates = updates): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const { registerSW } = await import('virtual:pwa-register');
    const updateSW = registerSW({
        immediate: true,
        onNeedRefresh() {
            // `true` tells the waiting worker to take over, then reloads once it has.
            store.found(() => updateSW(true));
        },
        onRegisteredSW(_url, registration) {
            // A phone left open for days still hears about a new version.
            if (registration !== undefined) {
                // Offline, the check fails; the next hour tries again.
                setInterval(() => registration.update().catch(() => undefined), HOUR);
            }
        },
    });
}
