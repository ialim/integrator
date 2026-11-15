import { Link, Outlet, useRouterState } from '@tanstack/react-router';
import { useCallback, useEffect, useRef, useState } from 'react';
import { restoreDraft, saveDraft } from '../drafts';
import { ServiceDownPage } from '../pages/pages';
import { useShell } from '../shell';
import { InstallOffer, UpdateBanner } from './Banners';

/**
 * Up unless `/api/v1/health` says otherwise, or the phone goes offline. Only
 * the latest check counts, and the service is never up while the browser
 * says it is offline. Going down keeps what was typed, so it comes back.
 */
function useServiceUp(): { up: boolean; retry: () => Promise<boolean> } {
    const { health } = useShell();
    const [up, setUp] = useState(true);
    const upNow = useRef(true);
    const latest = useRef(0);

    const settle = useCallback((result: boolean) => {
        const next = result && navigator.onLine !== false;

        if (!next && upNow.current) {
            saveDraft();
        }

        upNow.current = next;
        setUp(next);
    }, []);

    const check = useCallback(async () => {
        const id = ++latest.current;
        const result = await health();

        if (id === latest.current) {
            settle(result);
        }

        return result && navigator.onLine !== false;
    }, [health, settle]);

    useEffect(() => {
        const offline = () => {
            // An answer still on its way is older than this news.
            latest.current++;
            settle(false);
        };
        const online = () => void check();
        let mounted = true;
        const id = ++latest.current;
        void health().then((result) => {
            if (mounted && id === latest.current) {
                settle(result);
            }
        });
        window.addEventListener('offline', offline);
        window.addEventListener('online', online);

        return () => {
            mounted = false;
            window.removeEventListener('offline', offline);
            window.removeEventListener('online', online);
        };
    }, [health, check, settle]);

    return { up, retry: check };
}

/**
 * Every shell screen: the skip link first, the green top bar with the
 * wordmark, the update banner and install offer, the one main region, and
 * the text pages in the footer.
 */
export function Layout() {
    const main = useRef<HTMLElement>(null);
    const pathname = useRouterState({ select: (state) => state.location.pathname });
    const firstPath = useRef(pathname);
    const { up, retry } = useServiceUp();

    // After a page change, focus moves to the new page's content, as a fresh page load would.
    useEffect(() => {
        if (pathname !== firstPath.current) {
            firstPath.current = '';
            main.current?.focus();
        }
    }, [pathname]);

    // Values kept from before an update's reload, or from before the service went down, go back once the page is drawn.
    useEffect(() => {
        if (up) {
            restoreDraft();
        }
    }, [up]);

    return (
        <div className="shell">
            <a
                className="skip-link"
                href="#main"
                onClick={(event) => {
                    event.preventDefault();
                    main.current?.focus();
                }}
            >
                Skip to main content
            </a>
            <header className="topbar">
                <Link to="/" className="wordmark" aria-label="integrator, the start">
                    integrator
                </Link>
            </header>
            <UpdateBanner />
            <InstallOffer />
            <main id="main" ref={main} tabIndex={-1} className="content">
                {up ? <Outlet /> : <ServiceDownPage onRetry={retry} />}
            </main>
            <footer className="footer">
                <nav aria-label="About the app">
                    <ul className="footer-links">
                        <li>
                            <Link to="/install">Install the app</Link>
                        </li>
                        <li>
                            <Link to="/accessibility">Accessibility statement</Link>
                        </li>
                    </ul>
                </nav>
            </footer>
        </div>
    );
}
