import AxeBuilder from '@axe-core/playwright';
import { expect, type Page } from '@playwright/test';

/*
 * The checks every accessibility spec shares: the axe-core scan against
 * WCAG 2.2 AA (UX-DR22) and the no-sideways-scroll check.
 */
const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

export async function expectNoViolations(page: Page, where: string): Promise<void> {
    // Filament fades dialogs in and out; axe reads a half-faded colour as low contrast.
    // Endless animations (a loading spinner) never finish, so they are not waited for.
    // A finite animation that keeps restarting fails here quickly, not at the test's timeout.
    await page.waitForFunction(
        () =>
            document
                .getAnimations()
                .every(
                    (animation) =>
                        animation.playState !== 'running' || animation.effect?.getTiming().iterations === Infinity,
                ),
        undefined,
        { timeout: 15_000 },
    );
    const results = await new AxeBuilder({ page }).withTags(WCAG).analyze();
    const summary = results.violations.map(
        (violation) =>
            `${violation.id}: ${violation.help}\n    ${violation.nodes.map((node) => node.target.join(' ')).join('\n    ')}`,
    );

    expect(summary, `axe-core violations on ${where}`).toEqual([]);
}

export async function expectNoSidewaysScroll(page: Page, where: string): Promise<void> {
    const overflow = await page.evaluate(
        () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );

    expect(overflow, `sideways scroll on ${where}`).toBeLessThanOrEqual(0);
}
