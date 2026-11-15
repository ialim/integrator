/*
 * Renders the app icons in public/icons/ from the two SVG sources in
 * resources/icons/ with Playwright's Chromium. Run after changing a source:
 *   node scripts/render-icons.mjs
 * The PNGs are committed; the build does not render them.
 */
import { chromium } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const outputs = [
    { source: 'icon.svg', file: 'icon-192.png', size: 192 },
    { source: 'icon.svg', file: 'icon-512.png', size: 512 },
    { source: 'icon.svg', file: 'apple-touch-icon.png', size: 180 },
    { source: 'maskable.svg', file: 'maskable-512.png', size: 512 },
];

const browser = await chromium.launch();

try {
    for (const { source, file, size } of outputs) {
        const svg = readFileSync(resolve(root, 'resources/icons', source), 'utf8');
        const page = await browser.newPage({ viewport: { width: size, height: size } });
        await page.setContent(
            `<html><body style="margin:0">${svg.replace('width="512" height="512"', `width="${size}" height="${size}"`)}</body></html>`,
        );
        await page.screenshot({ path: resolve(root, 'public/icons', file), omitBackground: false });
        await page.close();
        console.log(`public/icons/${file} (${size}px)`);
    }
} finally {
    await browser.close();
}
