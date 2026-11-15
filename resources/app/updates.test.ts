import { describe, expect, it, vi } from 'vitest';
import { createUpdates, registerServiceWorker } from './updates';

type Options = {
    onNeedRefresh?: () => void;
    onRegisteredSW?: (url: string, registration: ServiceWorkerRegistration | undefined) => void;
};

const updateSW = vi.fn<(reload?: boolean) => Promise<void>>(async () => undefined);
let registered: Options = {};

vi.mock('virtual:pwa-register', () => ({
    registerSW: (options: Options) => {
        registered = options;

        return updateSW;
    },
}));

describe('registering the service worker', () => {
    it('waits for a new version, and applies it only when asked, by reloading into it', async () => {
        Object.defineProperty(navigator, 'serviceWorker', { value: {}, configurable: true });
        const store = createUpdates();

        await registerServiceWorker(store);
        expect(store.waiting()).toBe(false);

        registered.onNeedRefresh?.();
        expect(store.waiting()).toBe(true);
        expect(updateSW).not.toHaveBeenCalled();

        await store.apply();
        expect(updateSW).toHaveBeenCalledExactlyOnceWith(true);
    });

    it('checks for a new version every hour, and a failed check offline is not an error', async () => {
        vi.useFakeTimers();
        Object.defineProperty(navigator, 'serviceWorker', { value: {}, configurable: true });
        const update = vi.fn(async () => Promise.reject(new Error('offline')));

        await registerServiceWorker(createUpdates());
        registered.onRegisteredSW?.('/sw.js', { update } as unknown as ServiceWorkerRegistration);
        await vi.advanceTimersByTimeAsync(60 * 60 * 1000);

        expect(update).toHaveBeenCalledOnce();
        vi.useRealTimers();
    });
});
