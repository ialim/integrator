/*
 * Is the service reachable? `/api/v1/health` answers 200 when it is. A failed
 * answer, or no network at all, shows the service down page. The service
 * worker never answers `/api`, so an offline phone gets a real failure here.
 */

export type HealthCheck = () => Promise<boolean>;

export const HEALTH_URL = '/api/v1/health';

/** On slow 3G a stalled answer counts as down after this long, so the page never waits for ever. */
export const HEALTH_TIMEOUT_MS = 10_000;

export function createHealthCheck(
    fetchImpl: typeof fetch = (...args) => fetch(...args),
    timeoutMs: number = HEALTH_TIMEOUT_MS,
): HealthCheck {
    return async () => {
        try {
            const response = await fetchImpl(HEALTH_URL, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
                signal: AbortSignal.timeout(timeoutMs),
            });

            return response.ok;
        } catch {
            return false;
        }
    };
}
