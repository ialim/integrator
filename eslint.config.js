import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: [
            'public/**',
            'vendor/**',
            'node_modules/**',
            'storage/**',
            'bootstrap/**',
            '_bmad/**',
            '_bmad-output/**',
            '.claude/**',
            'test-results/**',
            'playwright-report/**',
        ],
    },
    {
        files: [
            'resources/app/**/*.{ts,tsx}',
            'resources/console/**/*.ts',
            'tests/Accessibility/**/*.ts',
            'vite.config.ts',
            'playwright.config.ts',
        ],
        extends: [js.configs.recommended, ...tseslint.configs.strict],
        languageOptions: {
            ecmaVersion: 2022,
            globals: globals.browser,
        },
        plugins: {
            'react-hooks': reactHooks,
            'react-refresh': reactRefresh,
        },
        rules: {
            ...reactHooks.configs.recommended.rules,
            'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
        },
    },
);
