import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { start } from './table-keys';

function page(): void {
    document.body.innerHTML = `
        <input id="search" type="search" data-console-search />
        <div data-console-table>
            <table>
                <caption>Staff accounts</caption>
                <tbody>
                    <tr data-row tabindex="-1" id="ada"><th>Ada</th><td><button id="ada-button">Reset sign-in</button></td></tr>
                    <tr data-row tabindex="-1" id="bola"><th>Bola</th><td><button id="bola-button">Deactivate</button></td></tr>
                    <tr data-row tabindex="-1" id="chidi"><th>Chidi</th><td></td></tr>
                </tbody>
            </table>
        </div>
    `;
}

function press(
    key: string,
    target: EventTarget = document.activeElement ?? document.body,
    init: KeyboardEventInit = {},
): KeyboardEvent {
    const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true, ...init });
    target.dispatchEvent(event);

    return event;
}

describe('console table keys', () => {
    let stop: () => void;

    beforeEach(() => {
        page();
        stop = start(document);
    });

    afterEach(() => stop());

    it('moves down with J and up with K, staying inside the list', () => {
        press('j');
        expect(document.activeElement?.id).toBe('ada');

        press('j');
        press('j');
        press('j');
        expect(document.activeElement?.id).toBe('chidi');
        expect(document.getElementById('chidi')?.hasAttribute('data-current')).toBe(true);
        expect(document.getElementById('ada')?.hasAttribute('data-current')).toBe(false);

        press('k');
        expect(document.activeElement?.id).toBe('bola');
    });

    it("goes to the row's first button with Enter", () => {
        press('j');
        press('j');
        const event = press('Enter');

        expect(document.activeElement?.id).toBe('bola-button');
        expect(event.defaultPrevented).toBe(true);
    });

    it('goes to the search box with /, and leaves typing in a field alone', () => {
        const slash = press('/');
        expect(document.activeElement?.id).toBe('search');
        expect(slash.defaultPrevented).toBe(true);

        const typed = press('j', document.getElementById('search') as HTMLElement);
        expect(typed.defaultPrevented).toBe(false);
        expect(document.activeElement?.id).toBe('search');
    });

    it('ignores keys with Ctrl, Alt or Meta held', () => {
        const event = press('j', document.body, { ctrlKey: true });

        expect(event.defaultPrevented).toBe(false);
        expect(document.activeElement).toBe(document.body);
    });
});
