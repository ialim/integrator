import { describe, expect, it } from 'vitest';
import { restoreDraft, saveDraft } from './drafts';

const KEY = 'integrator.update-draft';

function form(): HTMLElement {
    const root = document.createElement('div');
    root.innerHTML = `
        <input name="project" type="text" />
        <textarea id="notes"></textarea>
        <input name="room" type="radio" value="hall" />
        <input name="room" type="radio" value="kitchen" />
        <input name="secret" type="password" />
        <input name="code" type="text" data-no-draft />
        <input name="otp" type="text" autocomplete="one-time-code" />
        <input name="card" type="text" autocomplete="billing cc-number" />
        <input name="chosen" type="text" autocomplete="new-password" />
    `;

    return root;
}

function pick<T extends Element>(root: ParentNode, selector: string): T {
    const element = root.querySelector<T>(selector);

    if (element === null) {
        throw new Error(`No ${selector} in the form.`);
    }

    return element;
}

describe('keeping what was typed across an update', () => {
    it('puts the values back once, on the same page, never passwords or marked fields', () => {
        const storage = window.sessionStorage;
        const before = form();
        pick<HTMLInputElement>(before, '[name=project]').value = 'Lekki office';
        pick<HTMLTextAreaElement>(before, '#notes').value = 'Two cameras at the gate';
        pick<HTMLInputElement>(before, '[value=kitchen]').checked = true;
        pick<HTMLInputElement>(before, '[name=secret]').value = 'hunter2';
        pick<HTMLInputElement>(before, '[name=code]').value = '123456';
        pick<HTMLInputElement>(before, '[name=otp]').value = '654321';
        pick<HTMLInputElement>(before, '[name=card]').value = '4111111111111111';
        pick<HTMLInputElement>(before, '[name=chosen]').value = 'a new passphrase';

        saveDraft(before, storage, '/projects/1');
        const kept = storage.getItem(KEY) ?? '';
        expect(kept).not.toContain('654321');
        expect(kept).not.toContain('4111111111111111');
        expect(kept).not.toContain('a new passphrase');
        const after = form();

        expect(restoreDraft(after, storage, '/projects/1')).toBe(4);
        expect(pick<HTMLInputElement>(after, '[name=project]').value).toBe('Lekki office');
        expect(pick<HTMLTextAreaElement>(after, '#notes').value).toBe('Two cameras at the gate');
        expect(pick<HTMLInputElement>(after, '[value=kitchen]').checked).toBe(true);
        expect(pick<HTMLInputElement>(after, '[name=secret]').value).toBe('');
        expect(pick<HTMLInputElement>(after, '[name=code]').value).toBe('');

        expect(restoreDraft(form(), storage, '/projects/1')).toBe(0);
    });

    it('does not fill a different page', () => {
        saveDraft(form(), window.sessionStorage, '/projects/1');

        expect(restoreDraft(form(), window.sessionStorage, '/projects/2')).toBe(0);
    });

    it('keeps the draft until a form is there to fill', () => {
        const storage = window.sessionStorage;
        const before = form();
        pick<HTMLInputElement>(before, '[name=project]').value = 'Lekki office';
        saveDraft(before, storage, '/projects/1');

        expect(restoreDraft(document.createElement('div'), storage, '/projects/1')).toBe(0);
        expect(storage.getItem(KEY)).not.toBeNull();

        const later = form();
        expect(restoreDraft(later, storage, '/projects/1')).toBeGreaterThan(0);
        expect(pick<HTMLInputElement>(later, '[name=project]').value).toBe('Lekki office');
        expect(storage.getItem(KEY)).toBeNull();
    });

    it('treats a malformed draft as none', () => {
        const storage = window.sessionStorage;

        for (const raw of ['{', 'null', '{"path":"/projects/1"}', '{"path":"/projects/1","values":[1]}']) {
            storage.setItem(KEY, raw);
            expect(() => restoreDraft(form(), storage, '/projects/1')).not.toThrow();
            expect(restoreDraft(form(), storage, '/projects/1')).toBe(0);
        }
    });
});
