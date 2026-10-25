import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import './app.css';

const root = document.getElementById('app');

if (root === null) {
    throw new Error('The #app element is missing from the shell view.');
}

createRoot(root).render(
    <StrictMode>
        <App />
    </StrictMode>,
);
