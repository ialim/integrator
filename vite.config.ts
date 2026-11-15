import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { VitePWA } from 'vite-plugin-pwa';
import { defineConfig } from 'vitest/config';

/*
 * The service worker (AD-19). It lives at /sw.js so its scope is the whole
 * site, precaches the app shell's built assets from public/build, and answers
 * app navigations with the shell. It never caches or answers /api, /console
 * or /up, and leaves the console's own assets alone.
 */
const NEVER_ANSWERED = [/^\/api(\/|$)/, /^\/console(\/|$)/, /^\/up$/];

/** The shell's HTML names the hashed assets, so its revision follows them and the view. */
function shellRevision(entries: { url: string; revision?: string | null }[]): string {
    const hash = createHash('sha256');
    entries.forEach((entry) => hash.update(`${entry.url}:${entry.revision ?? ''}\n`));
    hash.update(readFileSync('resources/views/app.blade.php'));

    return hash.digest('hex').slice(0, 16);
}

// Vitest runs Vite's dev server (in `test` mode), which laravel-vite-plugin
// refuses to start in CI; the tests need none of its entry points.
export default defineConfig(({ mode }) => ({
    plugins: [
        mode !== 'test' &&
            laravel({
                input: [
                    'resources/app/main.tsx',
                    'resources/css/filament/console/theme.css',
                    'resources/console/session.ts',
                    'resources/console/table-keys.ts',
                ],
                refresh: true,
            }),
        react(),
        tailwindcss(),
        VitePWA({
            registerType: 'prompt',
            injectRegister: false,
            // sw.js goes to public/, served from the site root; the web manifest stays in public/build.
            outDir: 'public',
            base: '/',
            scope: '/',
            filename: 'sw.js',
            manifestFilename: 'manifest.webmanifest',
            includeManifestIcons: false,
            manifest: {
                id: '/',
                name: 'integrator',
                short_name: 'integrator',
                description: 'Built by an integrator for integrators in Nigeria.',
                lang: 'en-NG',
                dir: 'ltr',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'any',
                theme_color: '#153d2e',
                background_color: '#faf6ec',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
                    { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globDirectory: 'public/build',
                globPatterns: ['**/*.{js,css}'],
                // The console's theme and scripts are not the app's to cache.
                globIgnores: ['**/theme-*.css', '**/session-*.js', '**/table-keys-*.js', 'manifest.json'],
                modifyURLPrefix: { '': '/build/' },
                manifestTransforms: [
                    async (entries) => ({
                        manifest: [...entries, { url: '/', revision: shellRevision(entries), size: 0 }],
                        warnings: [],
                    }),
                ],
                navigateFallback: '/',
                navigateFallbackDenylist: NEVER_ANSWERED,
                // Hashed file names change with their content, so no cache-busting query is needed.
                dontCacheBustURLsMatching: /\/build\/assets\//,
                cleanupOutdatedCaches: true,
                clientsClaim: false,
                skipWaiting: false,
                runtimeCaching: [],
            },
            integration: {
                // The web manifest is read by the browser from /build, not by the app, so it is not precached.
                beforeBuildServiceWorker(options) {
                    options.workbox.additionalManifestEntries = (
                        options.workbox.additionalManifestEntries ?? []
                    ).filter((entry) => (typeof entry === 'string' ? entry : entry.url) !== 'manifest.webmanifest');
                },
            },
            devOptions: { enabled: false },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
    test: {
        environment: 'jsdom',
        include: [
            'resources/app/**/*.test.{ts,tsx}',
            'resources/console/**/*.test.ts',
            'resources/formats/**/*.test.ts',
        ],
        setupFiles: ['resources/app/test/setup.ts'],
    },
}));
