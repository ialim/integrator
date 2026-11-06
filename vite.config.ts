import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vitest/config';

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
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**'],
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/app/**/*.test.{ts,tsx}', 'resources/console/**/*.test.ts'],
        setupFiles: ['resources/app/test/setup.ts'],
    },
}));
