import { createMemoryHistory } from '@tanstack/react-router';
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { App } from './App';
import type { HealthCheck } from './health';
import { createPromptSource, type InstallEnvironment, type InstallPromptEvent } from './install';
import type { ShellServices } from './shell';
import { createUpdates } from './updates';

const NOW = new Date('2026-11-15T09:00:00Z');

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

function environment(overrides: Partial<InstallEnvironment> = {}): InstallEnvironment {
    return { storage: memoryStorage(), session: memoryStorage(), standalone: false, ios: false, ...overrides };
}

function services(overrides: Partial<ShellServices> = {}): ShellServices {
    return {
        health: async () => true,
        install: environment(),
        prompts: createPromptSource(window),
        updates: createUpdates(),
        now: () => NOW,
        ...overrides,
    };
}

async function open(path: string, shell: ShellServices = services()) {
    const result = render(<App services={shell} history={createMemoryHistory({ initialEntries: [path] })} />);
    await screen.findByRole('heading', { level: 1 });

    return result;
}

/** Chrome's install prompt, as the browser fires it. */
function firePrompt(outcome: 'accepted' | 'dismissed' = 'dismissed'): InstallPromptEvent {
    const event = new Event('beforeinstallprompt', { cancelable: true }) as InstallPromptEvent;
    Object.assign(event, {
        prompt: vi.fn(async () => undefined),
        userChoice: Promise.resolve({ outcome }),
    });
    act(() => {
        window.dispatchEvent(event);
    });

    return event;
}

afterEach(() => {
    document.title = '';
});

describe('the shell on every screen', () => {
    it.each([
        ['/', 'integrator'],
        ['/install', 'Install the app'],
        ['/accessibility', 'Accessibility statement'],
        ['/signed-out', 'You are signed out'],
        ['/nothing-here', 'We cannot find that page'],
    ])('%s has the skip link first, the landmarks and one top heading', async (path, title) => {
        await open(path);

        expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(title);
        expect(screen.getByRole('banner')).toBeInTheDocument();
        expect(screen.getByRole('main')).toBeInTheDocument();
        expect(screen.getByRole('navigation', { name: 'About the app' })).toBeInTheDocument();

        const focusable = document.querySelectorAll('a[href], button');
        expect(focusable[0]).toHaveTextContent('Skip to main content');
    });

    it('moves focus to the main content from the skip link', async () => {
        await open('/');

        fireEvent.click(screen.getByRole('link', { name: 'Skip to main content' }));

        expect(screen.getByRole('main')).toHaveFocus();
    });
});

describe('the holding screen', () => {
    it('shows the wordmark as the one top heading, and links to the text pages', async () => {
        await open('/');

        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('integrator');
        const footer = screen.getByRole('navigation', { name: 'About the app' });
        expect(within(footer).getByRole('link', { name: 'Install the app' })).toHaveAttribute('href', '/install');
        expect(within(footer).getByRole('link', { name: 'Accessibility statement' })).toHaveAttribute(
            'href',
            '/accessibility',
        );
    });

    it('moves focus to the new page after following a link', async () => {
        await open('/');

        fireEvent.click(
            within(screen.getByRole('navigation', { name: 'About the app' })).getByRole('link', {
                name: 'Install the app',
            }),
        );

        expect(await screen.findByRole('heading', { level: 1, name: 'Install the app' })).toBeInTheDocument();
        await waitFor(() => expect(screen.getByRole('main')).toHaveFocus());
    });
});

describe('the text pages', () => {
    it('gives the iPhone and Android steps in words on the Install page', async () => {
        await open('/install');

        expect(screen.getByRole('heading', { level: 2, name: 'On an iPhone' })).toBeInTheDocument();
        expect(screen.getByText(/Tap Share, the square with an arrow pointing up/)).toBeInTheDocument();
        expect(screen.getByText('Scroll down and tap Add to Home Screen.')).toBeInTheDocument();
        expect(screen.getByRole('heading', { level: 2, name: 'On an Android phone' })).toBeInTheDocument();
    });

    it('answers an unknown path with the not found page inside the template', async () => {
        await open('/nothing-here');

        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('We cannot find that page');
        expect(screen.getByRole('link', { name: 'Go to the start' })).toHaveAttribute('href', '/');
        expect(document.title).toBe('We cannot find that page · integrator');
    });

    it('dates the accessibility statement with the shared date format', async () => {
        await open('/accessibility');

        expect(screen.getByText('Last reviewed 15 Nov 2026.')).toBeInTheDocument();
    });
});

describe('service down', () => {
    it('shows the service down page when /api/v1/health fails', async () => {
        await open('/', services({ health: async () => false }));

        expect(
            await screen.findByRole('heading', { level: 1, name: 'integrator is not available right now' }),
        ).toBeInTheDocument();
        expect(screen.getAllByRole('heading', { level: 1 })).toHaveLength(1);
    });

    it('shows it when the phone goes offline, and comes back when the service answers', async () => {
        let up = true;
        const health: HealthCheck = async () => up;
        await open('/', services({ health }));

        up = false;
        act(() => {
            window.dispatchEvent(new Event('offline'));
        });
        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('integrator is not available right now');

        fireEvent.click(screen.getByRole('button', { name: 'Try again' }));
        expect(
            await screen.findByText('We tried again at 10:00 am and still could not reach integrator.'),
        ).toBeInTheDocument();

        up = true;
        act(() => {
            window.dispatchEvent(new Event('online'));
        });
        expect(await screen.findByRole('heading', { level: 1, name: 'integrator' })).toBeInTheDocument();
    });
});

describe('a new version', () => {
    it('waits behind the banner, announced politely without moving focus, and applies only when chosen', async () => {
        const updates = createUpdates();
        const activate = vi.fn(async () => undefined);
        await open('/', services({ updates }));
        const region = screen.getAllByRole('status')[0];
        const before = document.activeElement;

        act(() => updates.found(activate));

        expect(region).toHaveTextContent('A new version is ready. Update when you finish what you are doing');
        expect(document.activeElement).toBe(before);
        expect(activate).not.toHaveBeenCalled();

        fireEvent.click(screen.getByRole('button', { name: 'Update now' }));

        expect(activate).toHaveBeenCalledOnce();
    });

    it('keeps what was typed: the draft is written before the new version is applied', async () => {
        const field = document.body.appendChild(Object.assign(document.createElement('input'), { name: 'project' }));
        field.value = 'Lekki office';
        const updates = createUpdates();
        let keptAtApply: string | null = null;
        const activate = vi.fn(async () => {
            keptAtApply = window.sessionStorage.getItem('integrator.update-draft');
        });
        await open('/', services({ updates }));
        act(() => updates.found(activate));

        fireEvent.click(screen.getByRole('button', { name: 'Update now' }));

        expect(activate).toHaveBeenCalledOnce();
        expect(keptAtApply).toContain('Lekki office');
        field.remove();
    });

    it('drops the draft when the new version cannot be applied', async () => {
        const updates = createUpdates();
        await open('/', services({ updates }));
        act(() => updates.found(async () => Promise.reject(new Error('no worker'))));

        fireEvent.click(screen.getByRole('button', { name: 'Update now' }));

        await waitFor(() => expect(window.sessionStorage.getItem('integrator.update-draft')).toBeNull());
    });

    it('puts a kept draft back when the app opens on the same page', async () => {
        const field = document.body.appendChild(Object.assign(document.createElement('input'), { name: 'project' }));
        window.sessionStorage.setItem(
            'integrator.update-draft',
            JSON.stringify({ path: window.location.pathname, values: { project: 'Lekki office' } }),
        );

        await open('/');

        await waitFor(() => expect(field.value).toBe('Lekki office'));
        expect(window.sessionStorage.getItem('integrator.update-draft')).toBeNull();
        field.remove();
    });
});

describe('the install offer', () => {
    async function visit(install: InstallEnvironment) {
        const shell = services({ install, prompts: createPromptSource(window) });
        const view = await open('/', shell);

        return { view, shell };
    }

    function newSession(install: InstallEnvironment): InstallEnvironment {
        return { ...install, session: memoryStorage() };
    }

    it('is not made on the first visit', async () => {
        await visit(environment());
        firePrompt();

        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
    });

    it('is made once on the second visit, with the browser prompt on Android', async () => {
        const first = environment();
        const { view } = await visit(first);
        view.unmount();

        await visit(newSession(first));
        const prompt = firePrompt('accepted');
        const offer = screen.getByRole('complementary', { name: 'Install integrator on this phone' });

        fireEvent.click(within(offer).getByRole('button', { name: 'Install the app' }));

        await waitFor(() => expect(prompt.prompt).toHaveBeenCalledOnce());
        await waitFor(() =>
            expect(
                screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
            ).not.toBeInTheDocument(),
        );
    });

    it('links to the Install page on an iPhone', async () => {
        const first = environment({ ios: true });
        (await visit(first)).view.unmount();

        await visit(newSession(first));
        const offer = screen.getByRole('complementary', { name: 'Install integrator on this phone' });

        expect(within(offer).getByRole('link', { name: 'See how to install on an iPhone' })).toHaveAttribute(
            'href',
            '/install',
        );
    });

    it('ends for good when dismissed, and the Install page still works', async () => {
        const first = environment({ ios: true });
        (await visit(first)).view.unmount();
        const second = newSession(first);
        const { view } = await visit(second);

        fireEvent.click(screen.getByRole('button', { name: 'No thanks' }));
        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
        view.unmount();

        await visit(newSession(second));
        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();

        fireEvent.click(
            within(screen.getByRole('navigation', { name: 'About the app' })).getByRole('link', {
                name: 'Install the app',
            }),
        );
        expect(await screen.findByRole('heading', { level: 1, name: 'Install the app' })).toBeInTheDocument();
    });

    it('is not made when the app is opened from the home screen', async () => {
        const first = environment({ standalone: true, ios: true });
        (await visit(first)).view.unmount();
        await visit(newSession(first));

        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
    });

    it('is hidden on the Install page without being spent, and a later visit still gets it', async () => {
        const first = environment({ ios: true });
        (await visit(first)).view.unmount();
        const second = newSession(first);
        render(
            <App
                services={services({ install: second })}
                history={createMemoryHistory({ initialEntries: ['/install'] })}
            />,
        );
        await screen.findByRole('heading', { level: 1, name: 'Install the app' });

        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
        cleanup();

        await visit(newSession(second));
        expect(screen.getByRole('complementary', { name: 'Install integrator on this phone' })).toBeInTheDocument();
    });

    it('is not made when session storage cannot be used', async () => {
        const first = environment({ ios: true, session: null });
        (await visit(first)).view.unmount();
        await visit(first);

        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
    });

    it('is not made when storage cannot be read', async () => {
        await visit(environment({ storage: null, ios: true }));

        expect(
            screen.queryByRole('complementary', { name: 'Install integrator on this phone' }),
        ).not.toBeInTheDocument();
    });
});
