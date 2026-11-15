import { createContext, useContext } from 'react';
import type { HealthCheck } from './health';
import type { InstallEnvironment, PromptSource } from './install';
import type { Updates } from './updates';

/** What the shell's screens need from the browser, passed in so tests can supply their own. */
export interface ShellServices {
    health: HealthCheck;
    install: InstallEnvironment;
    prompts: PromptSource;
    updates: Updates;
    /** Our idea of now, for dates in words. Later epics read the server's clock. */
    now: () => Date;
}

export const ShellContext = createContext<ShellServices | null>(null);

export function useShell(): ShellServices {
    const services = useContext(ShellContext);

    if (services === null) {
        throw new Error('The shell services are missing: render inside <App>.');
    }

    return services;
}
