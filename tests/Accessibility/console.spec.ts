import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Locator, type Page } from '@playwright/test';
import { execSync } from 'node:child_process';
import { totp } from './totp';

/*
 * The staff console's accessibility criteria (Stories 1.3 to 1.6),
 * against a fresh database holding the founder's Admin, one invited
 * Operations account and one active Operations account with Support duties
 * Off (`app:seed-test-state first-admin invited-staff active-operations`).
 * The test values match database/factories/Staff/FirstAdminFactory.php,
 * StaffAccountFactory.php, InvitedStaffScenario.php and
 * ActiveOperationsScenario.php.
 */
const email = process.env.A11Y_STAFF_EMAIL ?? 'founder@example.test';
const password = process.env.A11Y_STAFF_PASSWORD ?? 'ledger-test-password';
const inviteeName = 'Bola Operations';
const invitationToken =
    process.env.A11Y_INVITATION_TOKEN ?? 'a11yInvitationToken0000000000000000000000000000000000000000000000';
const operationsName = 'Chika Operations';
const operationsEmail = process.env.A11Y_OPERATIONS_EMAIL ?? 'chika@example.test';
const operationsPassword = process.env.A11Y_OPERATIONS_PASSWORD ?? 'operations-test-passphrase';
const operationsSecret = process.env.A11Y_OPERATIONS_SECRET ?? 'KRUGS4ZANFZSAYJAORSXG5BAONSWG4TF';

const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

test.describe.configure({ mode: 'serial' });

let secret = '';

async function expectNoViolations(page: Page, where: string): Promise<void> {
    // Filament fades dialogs in and out; axe reads a half-faded colour as low contrast.
    // Endless animations (a loading spinner) never finish, so they are not waited for.
    await page.waitForFunction(() =>
        document
            .getAnimations()
            .every(
                (animation) =>
                    animation.playState !== 'running' || animation.effect?.getTiming().iterations === Infinity,
            ),
    );
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

async function firstStep(page: Page, as = { email, password }): Promise<void> {
    await page.goto('/console/login');
    await page.getByLabel('Email address').fill(as.email);
    await page.locator('input[type="password"]').fill(as.password);
    await page.getByRole('button', { name: 'Continue' }).click();
}

/** The rail: each group is a list named by the group's label. */
function railList(page: Page, group: string): Locator {
    return page.locator('.fi-sidebar-nav').getByRole('list', { name: group, exact: true });
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
    await expect(page.locator('[data-console-clock]')).toHaveText(/^\d{1,2}:\d{2} [ap]m$/);
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

test('the invitation page: one heading, labelled fields, errors in words, and a dead link said in words', async ({
    page,
}) => {
    await page.goto(`/console/invitation/${invitationToken}`);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Set up your staff console sign-in');
    await expectOneTopHeading(page);
    await expect(page.getByText(`Welcome, ${inviteeName}.`)).toBeVisible();
    await expectNoViolations(page, 'the invitation page');

    // Mismatched passwords are refused beside the field; the link is not used up.
    const first = page.getByLabel('New password');
    const again = page.getByLabel('Type the password again');
    await first.fill('a-long-enough-passphrase');
    await again.fill('a-different-passphrase');
    await page.getByRole('button', { name: 'Set the password and continue' }).click();
    await expect(page.getByText('The two passwords do not match.')).toBeVisible();
    await expectErrorTiedToField(page, again, 'The two passwords do not match.');
    await expectNoViolations(page, 'the invitation page with an error');
    await expectWorksAt200PercentZoom(page, 'the invitation page');

    await page.goto('/console/invitation/notARealInvitationToken');
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('This invitation link does not work');
    await expect(page.getByText('ask an Admin to send you a new one')).toBeVisible();
    await expectOneTopHeading(page);
    await expectNoViolations(page, 'an invitation link that does not work');
});

test('the Staff page: caption and headings, J, K, Enter and /, dialogs and 200% zoom', async ({ page }) => {
    await waitForNextCode();
    await firstStep(page);
    await page.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(secret));
    await page.waitForURL('**/console/today');

    await page.goto('/console/staff');
    await expectOneTopHeading(page);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Staff');

    // An Admin's rail: every group that has a page, each announced as a labelled list, the current place marked.
    await expect(railList(page, 'Queues')).toBeVisible();
    await expect(railList(page, 'People')).toBeVisible();
    await expect(railList(page, 'People').getByRole('link', { name: 'Staff' })).toHaveAttribute('aria-current', 'page');
    await expect(railList(page, 'Queues').getByRole('link', { name: 'Today' })).not.toHaveAttribute(
        'aria-current',
        'page',
    );

    const table = page.getByRole('table');
    await expect(table.locator('caption')).toContainText('Staff accounts: 3 people.');
    await expect(table.getByRole('columnheader')).toHaveText(['Name', 'Role', 'Duties', 'Status', 'Changes']);
    await expect(page.getByRole('row', { name: new RegExp(inviteeName) })).toContainText('Invited');
    await expectNoViolations(page, 'the Staff page');

    // The header stays in view: it is sticky inside the table's own scroll box.
    expect(
        await table
            .locator('thead th')
            .first()
            .evaluate((th) => getComputedStyle(th).position),
    ).toBe('sticky');

    // J and K move between rows, Enter goes to the row's first button, / goes to search.
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    await page.keyboard.press('j');
    await expect(page.locator('[data-row][data-current]')).toContainText('Ada Founder');
    await page.keyboard.press('j');
    await expect(page.locator('[data-row][data-current]')).toContainText(operationsName);
    await page.keyboard.press('k');
    await expect(page.locator('[data-row][data-current]')).toContainText('Ada Founder');
    expect(await focusRingWidth(page)).toBe('3px');
    await page.keyboard.press('j');
    await page.keyboard.press('Enter');

    // The Operations row's first control is its Support duties switch, announced with the person and the word Off.
    const supportSwitch = page.locator('[data-row][data-current]').getByRole('switch');
    await expect(supportSwitch).toBeFocused();
    await expect(supportSwitch).toHaveAccessibleName(`Support duties for ${operationsName}: Off`);
    await expect(supportSwitch).toHaveAttribute('aria-checked', 'false');
    expect(await focusRingWidth(page)).toBe('3px');
    await expectNoViolations(page, 'the Support duties switch, Off');

    // Enter switches it On; focus stays on the switch, which now says On.
    await page.keyboard.press('Enter');
    await expect(page.getByText(`Support duties are now On for ${operationsName}.`)).toBeVisible();
    const operationsSwitch = page.getByRole('row', { name: new RegExp(operationsName) }).getByRole('switch');
    await expect(operationsSwitch).toHaveAccessibleName(`Support duties for ${operationsName}: On`);
    await expect(operationsSwitch).toHaveAttribute('aria-checked', 'true');
    await expect(operationsSwitch).toBeFocused();
    await expect(page.getByRole('row', { name: new RegExp(operationsName) })).toContainText('Goods and Support');
    await expectNoViolations(page, 'the Support duties switch, On');

    // Space switches it back Off, so the Operations account's later checks start from the seeded state; focus stays.
    await page.keyboard.press('Space');
    await expect(page.getByText(`Support duties are now Off for ${operationsName}.`)).toBeVisible();
    await expect(operationsSwitch).toHaveAccessibleName(`Support duties for ${operationsName}: Off`);
    await expect(operationsSwitch).toHaveAttribute('aria-checked', 'false');
    await expect(operationsSwitch).toBeFocused();

    await page.keyboard.press('/');
    await expect(page.getByLabel('Search by name or email')).toBeFocused();

    // The add dialog.
    await page.getByRole('button', { name: 'Add a staff member' }).click();
    const addDialog = page.locator('.fi-modal-open .fi-modal-window');
    await expect(addDialog.getByRole('heading', { name: 'Add a staff member' })).toBeVisible();
    await expect(addDialog.getByLabel('Full name')).toBeVisible();
    await expectNoViolations(page, 'the add staff dialog');
    await page.keyboard.press('Escape');
    await expect(addDialog).toBeHidden();
    // Wait until no dialog is left, not even one still fading out, before the next scan.
    await expect(page.getByRole('dialog')).toHaveCount(0);

    // The deactivate dialog: a question naming the person, what will happen, red only on the irreversible button.
    await page
        .getByRole('row', { name: new RegExp(inviteeName) })
        .getByRole('button', { name: 'Deactivate' })
        .click();
    const dialog = page.locator('.fi-modal-open .fi-modal-window');
    await expect(dialog.getByRole('heading', { name: `Deactivate ${inviteeName}?` })).toBeVisible();
    await expect(dialog).toContainText('This cannot be undone');
    await expect(dialog.getByRole('button', { name: `Deactivate ${inviteeName}` })).toHaveClass(/fi-color-danger/);
    await expect(dialog.getByRole('button', { name: 'Keep the account' })).not.toHaveClass(/fi-color-danger/);
    await expectNoViolations(page, 'the deactivate dialog');
    await dialog.getByRole('button', { name: 'Keep the account' }).click();
    await expect(dialog).toBeHidden();
    // Wait until no dialog is left, not even one still fading out, before the next scan.
    await expect(page.getByRole('dialog')).toHaveCount(0);

    await expectWorksAt200PercentZoom(page, 'the Staff page');
});

test('the Audit trail page: Watch group, the chain check announced, Look up errors in words, History read-only', async ({
    page,
}) => {
    await waitForNextCode();
    await firstStep(page);
    await page.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(secret));
    await page.waitForURL('**/console/today');

    await page.goto('/console/audit-trail');
    await expectOneTopHeading(page);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Audit trail');
    await expect(railList(page, 'Watch').getByRole('link', { name: 'Audit trail' })).toHaveAttribute(
        'aria-current',
        'page',
    );

    const table = page.getByRole('table');
    await expect(table.locator('caption')).toContainText('of the audit trail, newest first.');
    await expect(table.getByRole('columnheader')).toHaveText([
        'Sequence',
        'When',
        'Who',
        'What',
        'Record',
        'Changes',
        'Seal',
    ]);
    await expect(page.getByRole('button', { name: /^(Edit|Delete|Export)/ })).toHaveCount(0);
    await expectNoViolations(page, 'the Audit trail page');

    // The chain check, by keyboard: its result is announced in a status region.
    const check = page.getByRole('button', { name: 'Check the chain' });
    await check.focus();
    expect(await focusRingWidth(page)).toBe('3px');
    await page.keyboard.press('Enter');
    const result = page.getByRole('status').filter({ hasText: 'The chain is' });
    await expect(result).toContainText(/The chain is intact/);
    await expect(result).toHaveAttribute('aria-live', 'polite');
    await expectNoViolations(page, 'the Audit trail page with the chain check result');

    // Look up refuses what is not an identifier, in words tied to the field.
    const lookup = page.getByLabel('Record or person identifier');
    await lookup.fill('not an identifier');
    await page.getByRole('button', { name: 'Look up', exact: true }).click();
    await expect(page.getByText('Enter an identifier like')).toBeVisible();
    await expectErrorTiedToField(page, lookup, 'Enter an identifier like');
    await expectNoViolations(page, 'the Audit trail page with a Look up error');
    await expectWorksAt200PercentZoom(page, 'the Audit trail page');

    // History on a staff row: focus moves into the dialog, its title names the person, no Edit or Delete.
    await page.goto('/console/staff');
    await page
        .getByRole('row', { name: new RegExp(operationsName) })
        .getByRole('button', { name: 'History' })
        .click();
    const dialog = page.locator('.fi-modal-open .fi-modal-window');
    await expect(dialog.getByRole('heading', { name: `History of ${operationsName}` })).toBeVisible();
    await expect
        .poll(() => page.evaluate(() => document.activeElement?.closest('.fi-modal-window') !== null))
        .toBe(true);
    await expect(dialog.getByRole('list', { name: 'History, newest first' })).toBeVisible();
    await expect(dialog.getByRole('button', { name: /^(Edit|Delete)/ })).toHaveCount(0);
    await expect(dialog.getByRole('button', { name: 'Close' }).first()).toBeVisible();
    await expectNoViolations(page, 'the History dialog');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('dialog')).toHaveCount(0);
});

test('Operations: a rail of its own groups only, and the Staff page refused in words naming who can', async ({
    page,
}) => {
    await firstStep(page, { email: operationsEmail, password: operationsPassword });
    await page.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(operationsSecret));
    await page.waitForURL('**/console/today');

    // Only the groups the role can use: Queues with Today, the current place. No People group, nothing greyed out.
    await expect(railList(page, 'Queues')).toBeVisible();
    await expect(railList(page, 'Queues').getByRole('link', { name: 'Today' })).toHaveAttribute('aria-current', 'page');
    await expect(page.locator('.fi-sidebar-nav').getByRole('list', { name: 'People' })).toHaveCount(0);
    await expect(page.locator('.fi-sidebar-nav').getByRole('link', { name: 'Staff' })).toHaveCount(0);
    await expectNoViolations(page, 'the Today page for Operations');

    // Following a link to the Staff page: refused by the server, inside the console shell, naming who can.
    const response = await page.goto('/console/staff');
    expect(response?.status()).toBe(403);
    await expectOneTopHeading(page);
    await expect(page.getByText('The Staff page is for Admins. Ask Ada Founder.')).toBeVisible();
    await expect(page.getByText('Staff console', { exact: true })).toBeVisible();
    await expect(page.getByText('Access denied')).toHaveCount(0);
    await expect(page.getByRole('table')).toHaveCount(0);
    await expectNoViolations(page, 'the refused Staff page');
    await expectWorksAt200PercentZoom(page, 'the refused Staff page');

    // The Audit trail page is not in the rail for Operations, and is refused on load in words naming who can.
    await expect(page.locator('.fi-sidebar-nav').getByRole('link', { name: 'Audit trail' })).toHaveCount(0);
    const auditResponse = await page.goto('/console/audit-trail');
    expect(auditResponse?.status()).toBe(403);
    await expectOneTopHeading(page);
    await expect(page.getByText('The Audit trail page is for Admins. Ask Ada Founder.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Check the chain' })).toHaveCount(0);
    await expectNoViolations(page, 'the refused Audit trail page');
});

test('with the app installed: the console still signs in and works, every page and Livewire call answered by the server', async ({
    page,
}) => {
    // The app's service worker covers the whole site; opening the app installs it.
    await page.goto('/');
    await page.evaluate(async () => {
        await navigator.serviceWorker.ready;
    });

    const fromWorker: string[] = [];
    let livewireCalls = 0;
    page.on('response', (response) => {
        const request = response.request();
        const path = new URL(response.url()).pathname;
        const isLivewireCall = request.method() === 'POST' && /^\/livewire(-[0-9a-f]+)?\//.test(path);
        livewireCalls += isLivewireCall ? 1 : 0;

        if ((request.resourceType() === 'document' || isLivewireCall) && response.fromServiceWorker()) {
            fromWorker.push(`${request.method()} ${path}`);
        }
    });

    // The previous test used this account's code; a code can be used once.
    await waitForNextCode();
    await firstStep(page, { email: operationsEmail, password: operationsPassword });
    await page.getByRole('textbox', { name: 'Code from your authenticator app' }).fill(totp(operationsSecret));
    await page.waitForURL('**/console/today');
    await expect(page.getByText('Staff console', { exact: true })).toBeVisible();

    const refused = await page.goto('/console/staff');
    expect(refused?.status()).toBe(403);
    await page.goto('/console/today');
    await expect(railList(page, 'Queues')).toBeVisible();

    // The worker really was in charge of these pages, or the check below proves nothing.
    expect(
        await page.evaluate(() => navigator.serviceWorker.controller !== null),
        'the worker controls the console',
    ).toBe(true);
    expect(livewireCalls, 'the sign-in steps went through Livewire').toBeGreaterThan(0);
    expect(fromWorker, 'console pages and Livewire calls answered by the worker').toEqual([]);
});
