import { Link, useRouterState } from '@tanstack/react-router';
import { useEffect, useState, useSyncExternalStore } from 'react';
import { clearDraft, saveDraft } from '../drafts';
import { dismiss, markOffered, offerKind, startVisit } from '../install';
import { useShell } from '../shell';

/**
 * "Update when you finish" (UX-DR24). The status region is always on the
 * page, so a screen reader announces the banner once when it appears, and
 * focus stays where the person is working.
 */
export function UpdateBanner() {
    const { updates } = useShell();
    const waiting = useSyncExternalStore(updates.subscribe, updates.waiting);

    return (
        <div role="status" className="banner-region">
            {waiting ? (
                <div className="banner banner-info">
                    <p>
                        A new version is ready. Update when you finish what you are doing; what you have typed is kept.
                    </p>
                    <button
                        type="button"
                        className="button button-secondary"
                        onClick={() => {
                            saveDraft();
                            // If the new version cannot be applied, the kept values must not come back later.
                            updates.apply().catch(() => clearDraft());
                        }}
                    >
                        Update now
                    </button>
                </div>
            ) : null}
        </div>
    );
}

/**
 * The install offer (UX-DR26): once, from the second visit, where the app is
 * not installed. Android opens the browser's prompt; an iPhone gets the
 * Install page. "No thanks" ends the offer for good on this browser.
 */
export function InstallOffer() {
    const { install, prompts } = useShell();
    const [allowed, setAllowed] = useState(() => startVisit(install));
    const prompt = useSyncExternalStore(prompts.subscribe, prompts.current);
    const installed = useSyncExternalStore(prompts.subscribe, prompts.installed);
    const onInstallPage = useRouterState({ select: (state) => state.location.pathname === '/install' });
    // Hidden on the Install page itself; the one offer is spent only when it is actually shown.
    const kind = allowed && !installed && !onInstallPage ? offerKind(install, prompt) : null;

    useEffect(() => {
        if (kind !== null) {
            markOffered(install);
        }
    }, [kind, install]);

    if (kind === null) {
        return null;
    }

    const end = () => {
        dismiss(install);
        setAllowed(false);
    };

    return (
        <aside className="banner banner-info install-offer" aria-labelledby="install-offer-title">
            <h2 id="install-offer-title" className="banner-title">
                Install integrator on this phone
            </h2>
            <p>It opens from your home screen, full screen, like any other app.</p>
            <div className="banner-actions">
                {kind === 'android' ? (
                    <button
                        type="button"
                        className="button"
                        onClick={async () => {
                            try {
                                await prompts.open();
                            } finally {
                                end();
                            }
                        }}
                    >
                        Install the app
                    </button>
                ) : (
                    <Link to="/install" className="button">
                        See how to install on an iPhone
                    </Link>
                )}
                <button type="button" className="button button-secondary" onClick={end}>
                    No thanks
                </button>
            </div>
        </aside>
    );
}
