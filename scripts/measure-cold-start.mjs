/*
 * The cold-start baseline (NFR-6): opens the built app shell in Chromium as
 * the test phone would, with no cache and no service worker, over the test
 * phone's network (400 kbps down and up, 400 ms round trip) with the CPU
 * slowed 4 times, and reports the time to the first usable screen (the
 * holding screen's "integrator" heading drawn). The median of the runs goes in
 * docs/operations/performance-baseline.md. "After the first byte" is the
 * shell's own share: the download and start-up of its assets, without the
 * server's time to answer.
 *
 *   npm run build
 *   node scripts/measure-cold-start.mjs [base-url] [runs]
 *
 * base-url defaults to A11Y_BASE_URL or http://127.0.0.1:8000; runs to 5.
 */
import { chromium, devices } from '@playwright/test';

const base = process.argv[2] ?? process.env.A11Y_BASE_URL ?? 'http://127.0.0.1:8000';
const runs = Number.parseInt(process.argv[3] ?? '5', 10);

// 400 kbps in bytes per second; latency is the round trip in milliseconds.
const NETWORK = { offline: false, latency: 400, downloadThroughput: 50_000, uploadThroughput: 50_000 };
const CPU_SLOWDOWN = 4;

const median = (values) => {
    const sorted = [...values].sort((a, b) => a - b);
    const middle = Math.floor(sorted.length / 2);

    return sorted.length % 2 === 1 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
};

const browser = await chromium.launch();
const results = [];

try {
    for (let run = 1; run <= runs; run++) {
        // A fresh context each run: no HTTP cache, no service worker, no storage.
        const context = await browser.newContext({ ...devices['Galaxy S5'], serviceWorkers: 'block' });
        const page = await context.newPage();
        const cdp = await context.newCDPSession(page);
        await cdp.send('Network.enable');
        await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
        await cdp.send('Network.emulateNetworkConditions', NETWORK);
        await cdp.send('Emulation.setCPUThrottlingRate', { rate: CPU_SLOWDOWN });

        let bytes = 0;
        cdp.on('Network.loadingFinished', (event) => {
            bytes += event.encodedDataLength;
        });

        await page.goto(base, { waitUntil: 'commit', timeout: 120_000 });
        // The holding screen's own heading: a service down page must not be timed as the first usable screen.
        await page.getByRole('heading', { level: 1, name: 'integrator', exact: true }).waitFor({ timeout: 120_000 });
        const timing = await page.evaluate(() => {
            const navigation = performance.getEntriesByType('navigation')[0];
            const paint = performance.getEntriesByName('first-contentful-paint')[0];

            return {
                ttfb: navigation ? navigation.responseStart : null,
                fcp: paint ? paint.startTime : null,
                usable: performance.now(),
            };
        });
        await page.waitForLoadState('networkidle', { timeout: 120_000 }).catch(() => undefined);

        results.push({
            ...timing,
            afterFirstByte: timing.ttfb === null ? null : timing.usable - timing.ttfb,
            kilobytes: bytes / 1024,
        });
        console.log(
            `run ${run}: usable ${(timing.usable / 1000).toFixed(2)} s, first paint ${
                timing.fcp === null ? 'n/a' : `${(timing.fcp / 1000).toFixed(2)} s`
            }, first byte ${timing.ttfb === null ? 'n/a' : `${(timing.ttfb / 1000).toFixed(2)} s`}, ${(bytes / 1024).toFixed(0)} KB`,
        );
        await context.close();
    }
} finally {
    await browser.close();
}

const pick = (key) => results.map((result) => result[key]).filter((value) => value !== null);
const seconds = (key) => (median(pick(key)) / 1000).toFixed(2);
console.log(
    `
median of ${runs}: usable ${seconds('usable')} s (${seconds('afterFirstByte')} s after the first byte), ` +
        `first paint ${seconds('fcp')} s, first byte ${seconds('ttfb')} s, ${median(pick('kilobytes')).toFixed(0)} KB transferred`,
);
