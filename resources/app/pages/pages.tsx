import { Link } from '@tanstack/react-router';
import { useEffect, useState, useSyncExternalStore } from 'react';
import { date, time } from '../../formats';
import { useShell } from '../shell';
import { TextPage } from './TextPage';

/** The signed-out holding screen: the wordmark, and the text pages in the footer. */
export function HoldingScreen() {
    useEffect(() => {
        document.title = 'integrator';
    }, []);

    return (
        <section className="holding" aria-labelledby="page-title">
            <h1 id="page-title" className="wordmark wordmark-large">
                integrator
            </h1>
            <p className="lead">Built by an integrator for integrators in Nigeria.</p>
            <p>Signing in opens here soon.</p>
            <p>
                You can <Link to="/install">install the app</Link> on your phone now, so it is ready when it opens.
            </p>
        </section>
    );
}

export function InstallPage() {
    const { prompts, install } = useShell();
    const prompt = useSyncExternalStore(prompts.subscribe, prompts.current);
    const [result, setResult] = useState<string | null>(null);

    return (
        <TextPage title="Install the app">
            <p>
                Installed, integrator opens from its own icon on your home screen, full screen, without the
                browser&rsquo;s address bar.
            </p>
            {install.standalone ? (
                <p>You have opened integrator from your home screen, so it is installed already.</p>
            ) : null}

            <h2 className="section-title">On an Android phone</h2>
            <p>You need Android 9 or newer, and Chrome.</p>
            {prompt !== null ? (
                <p>
                    <button
                        type="button"
                        className="button"
                        onClick={async () => {
                            let outcome: Awaited<ReturnType<typeof prompts.open>> = 'unavailable';

                            try {
                                outcome = await prompts.open();
                            } finally {
                                setResult(
                                    outcome === 'accepted'
                                        ? 'integrator is being added to your home screen.'
                                        : 'The install did not finish. Follow the steps below instead.',
                                );
                            }
                        }}
                    >
                        Install the app
                    </button>
                </p>
            ) : null}
            {result !== null ? <p role="status">{result}</p> : null}
            <ol className="steps">
                <li>Open this page in Chrome.</li>
                <li>Tap the menu, the three dots at the top right.</li>
                <li>Tap Install app. On some phones it says Add to Home screen.</li>
                <li>Tap Install.</li>
            </ol>

            <h2 className="section-title">On an iPhone</h2>
            <p>You need iOS 16.4 or newer.</p>
            <ol className="steps">
                <li>Open this page in Safari.</li>
                <li>Tap Share, the square with an arrow pointing up, at the bottom of the screen.</li>
                <li>Scroll down and tap Add to Home Screen.</li>
                <li>Tap Add, at the top right.</li>
            </ol>

            <p>
                integrator then opens from its icon on your home screen. To remove it, remove the icon as you would any
                app.
            </p>
        </TextPage>
    );
}

/** When this statement was last checked against the app. */
const STATEMENT_REVIEWED = new Date('2026-11-15T09:00:00Z');

export function AccessibilityPage() {
    const { now } = useShell();

    return (
        <TextPage title="Accessibility statement">
            <p>
                We want everyone who uses integrator to be able to use all of it. We aim to meet WCAG 2.2 at level AA.
            </p>

            <h2 className="section-title">What you can do</h2>
            <ul className="bullets">
                <li>Zoom in to 200% with nothing lost, on a phone from 320 pixels wide, held either way up.</li>
                <li>Make the text larger in your phone or browser settings.</li>
                <li>Use the app with only a keyboard. A focused control always shows a thick outline.</li>
                <li>
                    Use a screen reader such as TalkBack or VoiceOver. Every screen has one main heading and the same
                    landmarks.
                </li>
                <li>Turn on reduced motion. Nothing on the screen moves unless it helps.</li>
            </ul>

            <h2 className="section-title">What is not ready yet</h2>
            <p>
                This is the first part of the app: signing in and the Catalogue come next. We check every screen with an
                automated accessibility test on every change, and a person checks each release with a keyboard and a
                screen reader.
            </p>

            <h2 className="section-title">Tell us when something does not work</h2>
            <p>
                Call or WhatsApp us. Our number will be on this page before the app opens to businesses. Every report
                becomes a case, and we tell you what we did about it.
            </p>

            <p className="meta">Last reviewed {date(STATEMENT_REVIEWED, now(), true)}.</p>
        </TextPage>
    );
}

export function NotFoundPage() {
    return (
        <TextPage title="We cannot find that page">
            <p>The address may be mistyped, or the page may have moved.</p>
            <p>
                <Link to="/">Go to the start</Link>
            </p>
        </TextPage>
    );
}

export function SignedOutPage() {
    return (
        <TextPage title="You are signed out">
            <p>You are no longer signed in to integrator on this phone.</p>
            <p>
                <Link to="/">Go to the start</Link>
            </p>
        </TextPage>
    );
}

export function ServiceDownPage({ onRetry }: { onRetry: () => Promise<boolean> }) {
    const { now } = useShell();
    const [checking, setChecking] = useState(false);
    const [lastTry, setLastTry] = useState<Date | null>(null);

    return (
        <TextPage title="integrator is not available right now">
            <p>We could not reach integrator. Your signal may be weak, or our service may be down.</p>
            <p>Check your signal and try again. If it keeps happening, call or WhatsApp us.</p>
            <p>
                <button
                    type="button"
                    className="button"
                    onClick={async () => {
                        if (checking) {
                            return;
                        }

                        setChecking(true);
                        const up = await onRetry();

                        if (!up) {
                            setLastTry(now());
                            setChecking(false);
                        }
                    }}
                >
                    Try again
                </button>
            </p>
            <p role="status">
                {checking ? 'Trying again.' : null}
                {!checking && lastTry !== null
                    ? `We tried again at ${time(lastTry)} and still could not reach integrator.`
                    : null}
            </p>
        </TextPage>
    );
}
