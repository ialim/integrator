import { describe, expect, it } from 'vitest';
import {
    dismiss,
    eligible,
    markOffered,
    offerKind,
    startVisit,
    type InstallEnvironment,
    type InstallPromptEvent,
} from './install';

function memoryStorage(): Storage {
    const items = new Map<string, string>();

    return {
        get length() {
            return items.size;
        },
        clear: () => items.clear(),
        getItem: (key) => items.get(key) ?? null,
        key: (index) => [...items.keys()][index] ?? null,
        removeItem: (key) => void items.delete(key),
        setItem: (key, value) => void items.set(key, String(value)),
    };
}

/** A new browsing session on the same browser: same localStorage, fresh sessionStorage. */
function nextVisit(env: InstallEnvironment): InstallEnvironment {
    return { ...env, session: memoryStorage() };
}

const firstVisit = (): InstallEnvironment => ({
    storage: memoryStorage(),
    session: memoryStorage(),
    standalone: false,
    ios: false,
});

describe('the install offer rules', () => {
    it('counts one visit per browsing session, however many pages are opened', () => {
        const env = firstVisit();

        expect(startVisit(env)).toBe(false);
        expect(startVisit(env)).toBe(false);
        expect(startVisit(nextVisit(env))).toBe(true);
    });

    it('is offered once: shown on the second visit, not again on the third', () => {
        const env = firstVisit();
        startVisit(env);
        const second = nextVisit(env);

        expect(startVisit(second)).toBe(true);
        markOffered(second);
        expect(eligible(second)).toBe(true);

        expect(startVisit(nextVisit(second))).toBe(false);
    });

    it('ends when dismissed', () => {
        const env = firstVisit();
        startVisit(env);
        const second = nextVisit(env);
        startVisit(second);

        dismiss(second);

        expect(eligible(second)).toBe(false);
    });

    it('is never offered when installed, or when storage fails', () => {
        const env = { ...firstVisit(), standalone: true };
        startVisit(env);
        expect(startVisit(nextVisit(env))).toBe(false);

        const broken = memoryStorage();
        broken.getItem = () => {
            throw new Error('SecurityError');
        };
        expect(startVisit({ ...firstVisit(), storage: broken })).toBe(false);
        expect(startVisit({ ...firstVisit(), storage: null })).toBe(false);
    });

    it('uses the browser prompt on Android and the Install page on an iPhone', () => {
        const prompt = new Event('beforeinstallprompt') as InstallPromptEvent;

        expect(offerKind(firstVisit(), prompt)).toBe('android');
        expect(offerKind(firstVisit(), null)).toBeNull();
        expect(offerKind({ ...firstVisit(), ios: true }, null)).toBe('iphone');
    });
});
