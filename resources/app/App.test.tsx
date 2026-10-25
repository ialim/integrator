import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { App } from './App';

describe('App', () => {
    it('shows the wordmark as the one top heading', () => {
        render(<App />);

        expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('integrator');
    });
});
