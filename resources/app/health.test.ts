import { describe, expect, it, vi } from 'vitest';
import { createHealthCheck, HEALTH_URL } from './health';

describe('the health check', () => {
    it('is up on a 200 from /api/v1/health', async () => {
        const fetchStub = vi.fn(async () => new Response('{}', { status: 200 }));

        expect(await createHealthCheck(fetchStub)()).toBe(true);
        expect(fetchStub).toHaveBeenCalledWith(HEALTH_URL, expect.objectContaining({ cache: 'no-store' }));
    });

    it('is down on a 503', async () => {
        expect(await createHealthCheck(async () => new Response('', { status: 503 }))()).toBe(false);
    });

    it('is down when the network fails', async () => {
        expect(await createHealthCheck(async () => Promise.reject(new TypeError('Failed to fetch')))()).toBe(false);
    });

    it('is down when no answer comes before the timeout', async () => {
        const stalled: typeof fetch = (_url, init) =>
            new Promise((_resolve, reject) => {
                init?.signal?.addEventListener('abort', () => reject(init.signal?.reason));
            });

        expect(await createHealthCheck(stalled, 20)()).toBe(false);
    });
});
