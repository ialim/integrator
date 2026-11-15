import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { applyDraft, collectDraft, draftKey, formatWat, loadDraft, minutesLeftText, saveDraft, start } from './session';

const MEMBER = '01a17763-140f-71f9-b31f-70f86c209353';
const OTHER = '01a17763-140f-71f9-b31f-70f86c209354';

function form(): HTMLElement {
    const root = document.createElement('main');
    root.innerHTML = `
        <input id="data.reference" value="INT-0001" />
        <textarea id="data.note"></textarea>
        <input id="data.email" type="email" value="" />
        <input id="data.password" type="password" value="" />
        <input id="data.multiFactor.app.code" autocomplete="one-time-code" value="" />
        <input id="data.urgent" type="checkbox" />
        <input id="data.skip" value="" data-no-draft />
    `;
    document.body.replaceChildren(root);

    return root;
}

function field(root: ParentNode, id: string): HTMLInputElement | HTMLTextAreaElement {
    const found = root.querySelector<HTMLInputElement | HTMLTextAreaElement>(`#${CSS.escape(id)}`);

    if (found === null) {
        throw new Error(`No field ${id}`);
    }

    return found;
}

function type(root: ParentNode, id: string, value: string): void {
    field(root, id).value = value;
}

describe('the session draft', () => {
    beforeEach(() => window.sessionStorage.clear());

    it('keeps only values that changed, and never passwords, codes or opted-out fields', () => {
        const root = form();
        type(root, 'data.note', 'Call the client back');
        type(root, 'data.password', 'secret-password');
        type(root, 'data.multiFactor.app.code', '123456');
        type(root, 'data.skip', 'ignored');
        (field(root, 'data.urgent') as HTMLInputElement).checked = true;

        expect(collectDraft(root)).toEqual({ 'data.note': 'Call the client back', 'data.urgent': true });
    });

    it('saves nothing when nothing changed', () => {
        const root = form();
        saveDraft(window.sessionStorage, MEMBER, '/console/today', collectDraft(root));

        expect(loadDraft(window.sessionStorage, MEMBER, '/console/today')).toBeNull();
    });

    it('puts the values back and fires input events for Livewire', () => {
        const root = form();
        type(root, 'data.note', 'Call the client back');
        saveDraft(window.sessionStorage, MEMBER, '/console/today', collectDraft(root));

        const fresh = form();
        const seen: string[] = [];
        fresh.addEventListener('input', (event) => seen.push((event.target as HTMLElement).id));

        const draft = loadDraft(window.sessionStorage, MEMBER, '/console/today');
        expect(draft).not.toBeNull();
        expect(applyDraft(fresh, draft ?? {})).toBe(1);
        expect(field(fresh, 'data.note').value).toBe('Call the client back');
        expect(seen).toEqual(['data.note']);
    });

    it('keeps drafts per member and per page', () => {
        saveDraft(window.sessionStorage, MEMBER, '/console/today', { 'data.note': 'one' });

        expect(loadDraft(window.sessionStorage, MEMBER, '/console/other')).toBeNull();
        expect(loadDraft(window.sessionStorage, OTHER, '/console/today')).toBeNull();
    });
});

describe('the session clock', () => {
    it('shows West Africa Time, one hour ahead of UTC', () => {
        expect(formatWat(new Date('2026-10-26T08:05:00Z'))).toBe('9:05 am');
        expect(formatWat(new Date('2026-10-26T23:30:00Z'))).toBe('12:30 am');
    });

    it('counts the minutes left in words, never below one', () => {
        expect(minutesLeftText(300)).toBe('5 min');
        expect(minutesLeftText(61)).toBe('2 min');
        expect(minutesLeftText(60)).toBe('1 min');
        expect(minutesLeftText(5)).toBe('1 min');
    });
});

/* The page as the console renders it: the banner from session-banner.blade.php and a form. */
function consolePage(endsAt: string, now: string, member = MEMBER): void {
    document.head.innerHTML = '<meta name="csrf-token" content="token-1" />';
    document.body.innerHTML = `
        <time data-console-clock></time>
        <main id="fi-main-content">
            <div id="console-session" data-member="${member}" data-ends-at="${endsAt}" data-server-time="${now}"
                data-warning-seconds="300" data-extend-url="/console/session/extend">
                <div data-session-warning hidden>
                    <span data-session-minutes></span> <span data-session-ends-wat>9:00 pm</span>
                    <button type="button" data-session-stay>Stay signed in</button>
                </div>
                <div data-draft-banner hidden>
                    <button type="button" data-draft-restore>Put them back</button>
                    <button type="button" data-draft-discard>Discard them</button>
                </div>
                <dialog data-session-dialog>
                    <form data-session-form>
                        <input id="console-stay-code" data-no-draft />
                        <p data-session-error></p>
                        <button type="submit">Start a fresh 12 hours</button>
                    </form>
                </dialog>
            </div>
            <textarea id="data.note"></textarea>
        </main>
    `;
}

function element(selector: string): HTMLElement {
    const found = document.querySelector<HTMLElement>(selector);

    if (found === null) {
        throw new Error(`No element ${selector}`);
    }

    return found;
}

function jsonReply(status: number, body: object): Response {
    return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

async function submitCode(code: string): Promise<void> {
    (element('#console-stay-code') as HTMLInputElement).value = code;
    element('[data-session-form]').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    await vi.waitFor(() => expect(fetch).toHaveBeenCalled());
    await vi.advanceTimersByTimeAsync(0);
}

describe('the running session script', () => {
    let stop: () => void = () => undefined;
    const reload = vi.fn();

    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['setInterval', 'clearInterval', 'setTimeout', 'clearTimeout', 'Date'] });
        vi.setSystemTime(new Date('2026-10-26T19:50:00Z'));
        window.sessionStorage.clear();
        reload.mockReset();
        vi.stubGlobal('fetch', vi.fn());
    });

    afterEach(() => {
        stop();
        vi.unstubAllGlobals();
        vi.useRealTimers();
        delete window.Livewire;
    });

    it('shows the warning once 5 minutes are left, and keeps the West Africa Time clock current', async () => {
        consolePage('2026-10-26T20:00:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);

        expect(element('[data-session-warning]').hidden).toBe(true);
        expect(element('[data-console-clock]').textContent).toBe('8:50 pm');

        await vi.advanceTimersByTimeAsync(5 * 60 * 1000);

        expect(element('[data-session-warning]').hidden).toBe(false);
        expect(element('[data-session-minutes]').textContent).toBe('5 min');
    });

    it('hides the warning and moves the end time when "Stay signed in" succeeds', async () => {
        consolePage('2026-10-26T19:54:00Z', '2026-10-26T19:50:00Z');
        vi.mocked(fetch).mockResolvedValue(jsonReply(200, { ends_at: '2026-10-27T07:50:00Z', ends_at_wat: '8:50 am' }));
        stop = start(document, reload);
        expect(element('[data-session-warning]').hidden).toBe(false);

        await submitCode('123456');

        expect(fetch).toHaveBeenCalledWith('/console/session/extend', expect.objectContaining({ method: 'POST' }));
        expect(element('[data-session-warning]').hidden).toBe(true);
        expect(element('[data-session-ends-wat]').textContent).toBe('8:50 am');

        // The old end time no longer signs the person out.
        await vi.advanceTimersByTimeAsync(10 * 60 * 1000);
        expect(reload).not.toHaveBeenCalled();
    });

    it('shows the reason in words when the code is refused, without reading a non-JSON reply', async () => {
        consolePage('2026-10-26T19:54:00Z', '2026-10-26T19:50:00Z');
        vi.mocked(fetch).mockResolvedValue(new Response('<html>oops</html>', { status: 500 }));
        stop = start(document, reload);

        await submitCode('123456');

        expect(element('[data-session-error]').textContent).toBe('That did not work. Try again in a moment.');
        expect(reload).not.toHaveBeenCalled();
    });

    it.each([401, 419])('keeps a draft and reloads when the extend endpoint answers %i', async (status) => {
        consolePage('2026-10-26T19:54:00Z', '2026-10-26T19:50:00Z');
        vi.mocked(fetch).mockResolvedValue(new Response('', { status }));
        stop = start(document, reload);
        type(document, 'data.note', 'Unsaved note');

        await submitCode('123456');

        expect(reload).toHaveBeenCalledOnce();
        expect(window.sessionStorage.getItem(draftKey(MEMBER, window.location.pathname))).toContain('Unsaved note');
    });

    it.each([
        ['told the session ended', 401, { 'X-Staff-Session': 'ended' }],
        ['answered a bare 419', 419, {}],
    ])('keeps a draft and reloads when Livewire is %s', (_case, status, headers) => {
        type ErrorHandler = (context: { response: Response; preventDefault: () => void }) => void;
        const handlers: ErrorHandler[] = [];
        window.Livewire = {
            interceptRequest: (callback) => callback({ onError: (handler) => handlers.push(handler) }),
        };
        consolePage('2026-10-26T20:00:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);
        type(document, 'data.note', 'Unsaved note');

        const preventDefault = vi.fn();
        expect(handlers).toHaveLength(1);
        handlers[0]?.({
            response: new Response('', { status, headers }),
            preventDefault,
        });

        expect(preventDefault).toHaveBeenCalled();
        expect(reload).toHaveBeenCalledOnce();
        expect(window.sessionStorage.getItem(draftKey(MEMBER, window.location.pathname))).toContain('Unsaved note');
    });

    it('leaves any other Livewire error to Livewire', () => {
        type ErrorHandler = (context: { response: Response; preventDefault: () => void }) => void;
        const handlers: ErrorHandler[] = [];
        window.Livewire = {
            interceptRequest: (callback) => callback({ onError: (handler) => handlers.push(handler) }),
        };
        consolePage('2026-10-26T20:00:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);

        const preventDefault = vi.fn();
        handlers[0]?.({ response: new Response('', { status: 401 }), preventDefault });
        handlers[0]?.({ response: new Response('', { status: 500 }), preventDefault });

        expect(preventDefault).not.toHaveBeenCalled();
        expect(reload).not.toHaveBeenCalled();
    });

    it('offers a draft back to the same member and puts the values back', () => {
        saveDraft(window.sessionStorage, MEMBER, window.location.pathname, { 'data.note': 'Unsaved note' });
        consolePage('2026-10-27T07:50:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);

        expect(element('[data-draft-banner]').hidden).toBe(false);

        element('[data-draft-restore]').click();

        expect(field(document, 'data.note').value).toBe('Unsaved note');
        expect(element('[data-draft-banner]').hidden).toBe(true);
        expect(loadDraft(window.sessionStorage, MEMBER, window.location.pathname)).toBeNull();
    });

    it("never offers another member's draft, and drops it", () => {
        saveDraft(window.sessionStorage, OTHER, window.location.pathname, { 'data.note': 'Not yours' });
        consolePage('2026-10-27T07:50:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);

        expect(element('[data-draft-banner]').hidden).toBe(true);
        expect(loadDraft(window.sessionStorage, OTHER, window.location.pathname)).toBeNull();
    });

    it('moves the end time when the same member stays signed in from another tab', async () => {
        consolePage('2026-10-26T19:54:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);
        expect(element('[data-session-warning]').hidden).toBe(false);

        const otherTab = new BroadcastChannel('console-session');
        otherTab.postMessage({ member: OTHER, endsAt: '2026-10-27T09:00:00Z' });
        otherTab.postMessage({ member: MEMBER, endsAt: '2026-10-27T07:50:00Z', endsAtWat: '8:50 am' });
        await vi.waitFor(() => expect(element('[data-session-ends-wat]').textContent).toBe('8:50 am'));
        otherTab.close();

        expect(element('[data-session-warning]').hidden).toBe(true);
    });

    it('signs out at the end time, keeping a draft first', async () => {
        consolePage('2026-10-26T19:51:00Z', '2026-10-26T19:50:00Z');
        stop = start(document, reload);
        type(document, 'data.note', 'Unsaved note');

        await vi.advanceTimersByTimeAsync(75 * 1000);

        expect(reload).toHaveBeenCalledOnce();
        expect(loadDraft(window.sessionStorage, MEMBER, window.location.pathname)).toEqual({
            'data.note': 'Unsaved note',
        });
    });
});
