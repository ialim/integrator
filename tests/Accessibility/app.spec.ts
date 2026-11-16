import { devices, expect, test, type Page } from '@playwright/test';
import { expectNoSidewaysScroll, expectNoViolations } from './checks';

/*
 * The Integrator app shell's accessibility criteria (Story 1.7, UX-DR22):
 * every shell screen at 320px in both orientations and at desktop width, at
 * 200% zoom, with no axe-core violations, a skip link first, one top heading,
 * landmarks and a visible 3px focus ring. It needs the built app (npm run
 * build) served by Laravel; no database rows are needed.
 */
const SCREENS = [
    { name: 'the holding screen', path: '/', heading: 'integrator' },
    { name: 'the Install page', path: '/install', heading: 'Install the app' },
    { name: 'the accessibility statement', path: '/accessibility', heading: 'Accessibility statement' },
    { name: 'the signed-out page', path: '/signed-out', heading: 'You are signed out' },
    { name: 'the not found page', path: '/nothing-here', heading: 'We cannot find that page' },
];

const SIZES = [
    { label: 'a 320px phone, upright', width: 320, height: 640 },
    { label: 'a 320px phone, on its side', width: 640, height: 320 },
    { label: 'a desktop', width: 1280, height: 800 },
    // 200% zoom of a 1280 by 800 window lays out as 640 by 400.
    { label: 'a desktop at 200% zoom', width: 640, height: 400 },
];

async function expectShell(page: Page, heading: string): Promise<void> {
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.getByRole('banner')).toHaveCount(1);
    await expect(page.getByRole('main')).toHaveCount(1);
    await expect(page.getByRole('navigation', { name: 'About the app' })).toHaveCount(1);
}

/** Tabs through the screen: the skip link comes first, and every control shows a 3px ring. */
async function expectKeyboardOrder(page: Page): Promise<void> {
    const controls = await page.locator('a[href], button').count();

    for (let i = 0; i < controls; i++) {
        await page.keyboard.press('Tab');
        const focused = await page.evaluate(() => {
            const active = document.activeElement as HTMLElement | null;

            return {
                text: active?.textContent?.trim() ?? '',
                ring: active === null ? '0px' : getComputedStyle(active).outlineWidth,
                style: active === null ? 'none' : getComputedStyle(active).outlineStyle,
            };
        });

        if (i === 0) {
            expect(focused.text).toBe('Skip to main content');
        }

        expect(focused.ring, `focus ring on "${focused.text}"`).toBe('3px');
        expect(focused.style, `focus ring on "${focused.text}"`).not.toBe('none');
    }
}

for (const screen of SCREENS) {
    test(`${screen.name}: no violations at 320px both ways, desktop and 200% zoom; skip link first; 3px ring`, async ({
        page,
    }) => {
        for (const size of SIZES) {
            await page.setViewportSize({ width: size.width, height: size.height });
            await page.goto(screen.path);
            await expectShell(page, screen.heading);
            await expectNoSidewaysScroll(page, `${screen.name} on ${size.label}`);
            await expectNoViolations(page, `${screen.name} on ${size.label}`);
        }

        await page.setViewportSize({ width: 320, height: 640 });
        await page.goto(screen.path);
        await expectKeyboardOrder(page);
    });
}

test('larger text: at 200% text size every shell screen stays one column with no sideways scroll at 320px', async ({
    page,
}) => {
    await page.setViewportSize({ width: 320, height: 640 });
    // The browser's text size setting raises the root font size, which the rem-based Ledger sizes follow.
    await page.addInitScript(() => {
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.style.fontSize = '200%';
        });
    });

    for (const screen of SCREENS) {
        await page.goto(screen.path);
        await expectShell(page, screen.heading);
        const sizes = await page.evaluate(() => ({
            root: getComputedStyle(document.documentElement).fontSize,
            body: getComputedStyle(document.body).fontSize,
        }));

        expect(sizes.root, `root text size on ${screen.name}`).toBe('32px');
        expect(Number.parseFloat(sizes.body), `body text follows the larger size on ${screen.name}`).toBe(30);
        await expectNoSidewaysScroll(page, `${screen.name} with 200% text`);
    }
});

test('reduced motion: nothing transitions or animates on any shell screen', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });

    for (const screen of SCREENS) {
        await page.goto(screen.path);
        await expectShell(page, screen.heading);
        // Focusing the skip link is the one place the shell changes how something looks.
        await page.keyboard.press('Tab');
        const moving = await page.evaluate(() => {
            const found: string[] = [];

            for (const element of [document.documentElement, ...document.querySelectorAll('*')]) {
                const style = getComputedStyle(element);
                const durations = `${style.transitionDuration},${style.animationDuration}`
                    .split(',')
                    .map((value) => Number.parseFloat(value));

                if (style.animationName !== 'none' || durations.some((value) => value > 0)) {
                    found.push(
                        element.tagName.toLowerCase() + (element.className ? `.${String(element.className)}` : ''),
                    );
                }
            }

            return { found, running: document.getAnimations().length };
        });

        expect(moving.found, `transitions or animations on ${screen.name}`).toEqual([]);
        expect(moving.running, `running animations on ${screen.name}`).toBe(0);
    }
});

test('an unknown path is answered by the shell with HTTP 200 and the not found page', async ({ page }) => {
    const response = await page.goto('/nothing-here');

    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('We cannot find that page');
});

test('the service down page: shown when /api/v1/health fails, no violations at 320px and 200% zoom', async ({
    page,
}) => {
    await page.route('**/api/v1/health', (route) => route.abort('internetdisconnected'));

    for (const size of SIZES) {
        await page.setViewportSize({ width: size.width, height: size.height });
        await page.goto('/');
        await expectShell(page, 'integrator is not available right now');
        await expectNoSidewaysScroll(page, `the service down page on ${size.label}`);
        await expectNoViolations(page, `the service down page on ${size.label}`);
    }

    await page.setViewportSize({ width: 320, height: 640 });
    await expectKeyboardOrder(page);
});

test.describe('on an iPhone, second visit', () => {
    // The iPhone's screen and user agent, still in Chromium: a describe group cannot switch browsers.
    const { viewport, userAgent, deviceScaleFactor, isMobile, hasTouch } = devices['iPhone SE'];
    test.use({ viewport, userAgent, deviceScaleFactor, isMobile, hasTouch });

    test('the install offer links to the Install page, has no violations, and ends when dismissed', async ({
        page,
    }) => {
        // The first visit happened in an earlier browsing session.
        await page.addInitScript(() => {
            if (window.localStorage.getItem('integrator.install.visits') === null) {
                window.localStorage.setItem('integrator.install.visits', '1');
            }
        });
        await page.goto('/');
        const offer = page.getByRole('complementary', { name: 'Install integrator on this phone' });

        await expect(offer).toBeVisible();
        await expect(offer.getByRole('link', { name: 'See how to install on an iPhone' })).toHaveAttribute(
            'href',
            '/install',
        );
        await expectNoSidewaysScroll(page, 'the install offer');
        await expectNoViolations(page, 'the holding screen with the install offer');
        await expectKeyboardOrder(page);

        await offer.getByRole('button', { name: 'No thanks' }).click();
        await expect(offer).toHaveCount(0);
        await page.reload();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('integrator');
        await expect(offer).toHaveCount(0);
    });
});

test('the service worker answers app navigations, never the server routes beside the app', async ({ page }) => {
    // Livewire's route prefix carries a hash; read it from the console's own page.
    await page.goto('/console/login');
    const livewireScript = (await page.locator('script[src*="/livewire"]').first().getAttribute('src')) ?? '';
    const livewirePrefix = new URL(livewireScript, page.url()).pathname.split('/')[1];
    expect(livewirePrefix, 'Livewire 4 hashes its route prefix').toMatch(/^livewire-[0-9a-f]+$/);

    await page.goto('/');
    await page.evaluate(async () => {
        await navigator.serviceWorker.ready;
    });

    const app = await page.goto('/nothing-here');
    expect(app?.fromServiceWorker(), '/nothing-here from the worker').toBe(true);

    for (const path of [
        '/console/login',
        '/api/v1/health',
        '/up',
        '/filament/exports/1/download',
        `/${livewirePrefix}/preview-file/nothing.png`,
        '/storage/nothing.png',
        '/sanctum/csrf-cookie',
        '/robots.txt',
    ]) {
        // Read the response itself: an empty answer (Sanctum's 204) aborts the page load.
        const [response] = await Promise.all([
            page.waitForResponse((answer) => new URL(answer.url()).pathname === path),
            page.goto(path).catch(() => null),
        ]);
        expect(response.fromServiceWorker(), `${path} from the network`).toBe(false);
    }
});
