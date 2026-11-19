import { defineConfig, devices } from '@playwright/test';

/*
 * The automated accessibility check (axe-core) of every Integrator app shell
 * screen (tests/Accessibility/app.spec.ts: built assets only, no rows needed)
 * and of the staff console's sign-in,
 * second-step, set-up, Today, Staff, Audit trail, Pricing and rules, refused
 * and invitation pages, the Staff and Pricing and rules pages' dialogs, the session warning and its dialog, the
 * phone notice, and the rail per role. The shared checks are in checks.ts.
 * It needs the app running with a fresh database seeded by
 * `php artisan app:seed-test-state first-admin invited-staff active-operations active-finance product-ranges`; CI does this
 * in the `accessibility` job. A11Y_BASE_URL points it elsewhere.
 */
export default defineConfig({
    testDir: 'tests/Accessibility',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 300_000,
    expect: { timeout: 60_000 },
    forbidOnly: Boolean(process.env.CI),
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.A11Y_BASE_URL ?? 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } }],
});
