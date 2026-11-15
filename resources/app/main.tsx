import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import { createHealthCheck } from './health';
import { browserEnvironment, createPromptSource } from './install';
import { registerServiceWorker, updates } from './updates';
import './app.css';

// Chrome can offer its install prompt before React mounts, so listen first.
const prompts = createPromptSource();

const root = document.getElementById('app');

if (root === null) {
    throw new Error('The #app element is missing from the shell view.');
}

createRoot(root).render(
    <StrictMode>
        <App
            services={{
                health: createHealthCheck(),
                install: browserEnvironment(),
                prompts,
                updates,
                now: () => new Date(),
            }}
        />
    </StrictMode>,
);

if (import.meta.env.PROD) {
    void registerServiceWorker();
}
