import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/app/main.tsx',
                'resources/css/filament/console/theme.css',
                'resources/console/session.ts',
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
});
