import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Locator, type Page } from '@playwright/test';
import { execSync } from 'node:child_process';
import { totp } from './totp';

/*
 * The staff console's accessibility criteria (Story 1.3), against a fresh
 * database holding only the founder's Admin (`app:seed-test-state first-admin`).
 * The test values match database/factories/Staff/FirstAdminFactory.php.
 */
const email = process.env.A11Y_STAFF_EMAIL ?? 'founder@example.test';
const password = process.env.A11Y_STAFF_PASSWORD ?? 'ledger-test-password';

const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

test.describe.configure({ mode: 'serial' });

let secret = '';

async function expectNoViolations(page: Page, where: string): Promise<void> {
    const results = await new AxeBuilder({ page }).withTags(WCAG).analyze();
    const summary = results.violations.map(
        (violation) =>
            `${violation.id}: ${violation.help}\n    ${violation.nodes.map((node) => node.target.join(' ')).join('\n    ')}`,
    );

    expect(summary, `axe-core violations on ${where}`).toEqual([]);
}

async function expectOneTopHeading(page: Page): Promise<void> {
    await expect(page.locator('h1')).toHaveCount(1);
}

async function expectNoSidewaysScroll(page: Page): Promise<void> {
    const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );

    expect(overflow).toBeLessThanOrEqual(0);
}

/** The ring is drawn on the control, or on Filament's field wrapper around an input. */
async function focusRingWidth(page: Page): Promise<string> {
    return page.evaluate(() => {
        const active = document.activeElement;

        if (!(active instanceof HTMLElement)) {
            return '0px';
        }

        const target = active.closest('.fi-input-wrp') ?? active;

        return getComputedStyle(target).outlineWidth;
    });
}

async function firstStep(page: Page): Promise<void> {
    await page.goto('/console/login');
    await page.getByLabel('Email address').fill(email);
    await page.locator('input[type="password"]').fill(password);
    await page.getByRole('button', { name: 'Continue' }).click();
}

/** The field's error is in words, and the field points at it with aria-describedby. */
async function expectErrorTiedToField(page: Page, field: Locator, message: string): Promise<void> {
    await expect(field).toHaveAttribute('aria-invalid', 'true');

    const ids = ((await field.getAttribute('aria-describedby')) ?? '').split(/\s+/).filter(Boolean);
    const described = await Promise.all(
        ids.map((id) =>
            page
                .locator(`[id="${id}"]`)
                .innerText()
                .catch(() => ''),
        ),
    );

    expect(described.join(' '), 'the error is referenced by aria-describedby').toContain(message);
}

async function expectWorksAt200PercentZoom(page: Page, where: string): Promise<void> {
    const size = page.viewportSize();
    await page.setViewportSize({ width: 640, height: 400 });
    await expectNoSidewaysScroll(page);
    await expectNoViolations(page, `${where} at 200% zoom`);

    if (size !== null) {
        await page.setViewportSize(size);
    }
}

/*
 * Filament allows 5 sign-in attempts a minute. CI clears the rate limits
 * (the database cache table) before each test through A11Y_RESET_LIMITS_CMD,
 * so a fast runner never trips the limit; the app's limits stay as they are.
 */
test.beforeEach(() => {
    const command = process.env.A11Y_RESET_LIMITS_CMD;

    if (command) {
        execSync(command, { stdio: 'inherit' });
    }
});

async function waitForNextCode(): Promise<void> {
    // A code can be used once; wait for the next 30-second step before reusing the app.
    const msIntoStep = Date.now() % 30_000;
    await new Promise((resolve) => setTimeout(resolve, 30_000 - msIntoStep + 500));
}

test('the sign-in page: no violations, reading-order focus with a 3px ring, errors in words', async ({ page }) => {
    await page.goto('/console/login');
    await expectOneTopHeading(page);
    await expectNoViolations(page, 'the sign-in page');

    // The email field has focus on arrival; Tab then moves on in reading order.
    await expect(page.getByLabel('Email address')).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    const order: string[] = [];

    for (let i = 0; i < 4; i++) {
        await page.keyboard.press('Tab');
        order.push(
            await page.evaluate(() => {
                const el = document.activeElement as HTMLElement | null;

                return el?.getAttribute('type') === 'password' ? 'password' : el?.id || el?.textContent?.trim() || '';
            }),
        );
        expect(await focusRingWidth(page)).toBe('3px');
    }

    expect(order[0]).toContain('email');
    expect(order[1]).toBe('password');
    expect(order.at(-1)).toBe('Continue');

    await page.getByLabel('Email address').fill(email);
    await page.locator('input[type="password"]').fill('not-the-password');
    await page.getByRole('button', { name: 'Continue' }).click();

    await expect(page.getByText('The email address or password is not right.')).toBeVisible();
    await expectErrorTiedToField(page, page.getByLabel('Email address'), 'The email address or password is not right.');
    await expectNoViolations(page, 'the sign-in page with an error');
    await expectWorksAt200PercentZoom(page, 'the sign-in page');
});

test('the set-up page: a staff member without a second factor sets one up first', async ({ page }) => {
    await firstStep(page);
    await page.waitForURL('**/console/multi-factor-authentication/set-up');
    await expectOneTopHeading(page);
    await expectNoViolations(page, 'the set-up page');
    await expectWorksAt200PercentZoom(page, 'the set-up page');

    // No other console page opens yet.
    await page.goto('/console/today');
    await page.waitForURL('**/console/multi-factor-authentication/set-up');

    await page.getByRole('button', { name: 'Set up the authenticator app' }).click();
    const dialog = page.locator('.fi-modal-open .fi-modal-window');
    await expect(dialog).toBeVisible();

    await expect(dialog).toContainText('Or type this key into the app');
    secret = /^[A-Z2-7]{16,}$/m.exec(await dialog.innerText())?.[0] ?? '';
    expect(secret).toMatch(/^[A-Z2-7]{16,}$/);

    await expectNoViolations(page, 'the set-up dialog');

    await dialog.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(secret));
    await dialog.getByLabel('Your password').fill(password);
    await dialog.getByRole('button', { name: 'Next' }).click();
    await expect(dialog.getByText('Keep these recovery codes somewhere safe')).toBeVisible();
    await dialog.getByRole('button', { name: 'Turn on the authenticator app' }).click();

    await page.getByRole('button', { name: 'Go to the console' }).click();
    await page.waitForURL('**/console/today');
});

test('the Today page and the shell: one heading, skip link, landmarks, 200% zoom', async ({ page }) => {
    await waitForNextCode();
    await firstStep(page);
    // The second-step field submits itself once all six digits are in (Filament's submitOnCompletion).
    await page.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(secret));
    await page.waitForURL('**/console/today');

    await expectOneTopHeading(page);
    await expect(page.getByRole('main')).toHaveCount(1);
    await expect(page.getByText('Staff console', { exact: true })).toBeVisible();
    await expect(page.locator('[data-console-clock]')).toHaveText(/^\d{2}:\d{2} WAT$/);
    await expectNoViolations(page, 'the Today page');

    // The skip link is the first stop and passes the menu.
    await page.keyboard.press('Tab');
    const skip = page.getByRole('link', { name: 'Skip to the page' });
    await expect(skip).toBeFocused();
    expect(await focusRingWidth(page)).toBe('3px');
    await page.keyboard.press('Enter');
    await expect(page.locator('#fi-main-content')).toBeFocused();

    // 200% zoom of a 1280px window: the rail becomes a menu button, nothing scrolls sideways.
    await page.setViewportSize({ width: 640, height: 400 });
    await expect(page.getByRole('button', { name: 'Open the menu' })).toBeVisible();
    await expectNoSidewaysScroll(page);
    await expectNoViolations(page, 'the Today page at 200% zoom');

    // A phone gets the notice that the console is for a desktop.
    await page.setViewportSize({ width: 375, height: 812 });
    await expect(page.getByText('The staff console is made for a desktop.')).toBeVisible();
    await expectNoSidewaysScroll(page);
});

test('the second step: no violations, a wrong code is refused in words beside the field', async ({ page }) => {
    await firstStep(page);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Enter your authenticator code');
    await expectOneTopHeading(page);
    await expectNoViolations(page, 'the second step');

    const code = page.getByRole('textbox', { name: 'Code from your authenticator app' });
    const current = totp(secret);
    // The field submits itself once all six digits are in, so no click is needed.
    await code.fill(current === '000000' ? '111111' : '000000');

    await expect(page.getByText('That code is not right.')).toBeVisible();
    await expectErrorTiedToField(page, code, 'That code is not right.');
    await expectNoViolations(page, 'the second step with an error');

    await page.setViewportSize({ width: 640, height: 400 });
    await expectNoSidewaysScroll(page);
    await expectNoViolations(page, 'the second step at 200% zoom');
});
