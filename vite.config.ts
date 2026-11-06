import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vitest/config';

// Vitest runs Vite's dev server, which laravel-vite-plugin refuses to start
// in CI; the tests need none of its entry points, so it loads only for Vite.
const forVite = process.env.VITEST === undefined;

export default defineConfig({
    plugins: [
        forVite &&
            laravel({
                input: ['resources/app/main.tsx'],
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
        include: ['resources/app/**/*.test.{ts,tsx}'],
        setupFiles: ['resources/app/test/setup.ts'],
    },
});
