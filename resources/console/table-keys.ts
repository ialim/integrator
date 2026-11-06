/*
 * Keyboard movement in console tables (UX: J, K, Enter and /). Inside a
 * `[data-console-table]`, J moves to the next row and K to the previous one,
 * Enter goes to the focused row's first button, and / goes to the page's
 * `[data-console-search]` box. Keys typed into a field, or with Ctrl, Alt or
 * Meta held, are left alone. Rows carry `data-row` and tabindex="-1"; the
 * current one is marked `data-current`.
 */

const OPEN_DIALOG = '.fi-modal-open, dialog[open]';

const TYPING = 'input, textarea, select, [contenteditable=""], [contenteditable="true"]';

function rows(doc: Document): HTMLElement[] {
    return Array.from(doc.querySelectorAll<HTMLElement>('[data-console-table] [data-row]'));
}

function isTyping(target: EventTarget | null): boolean {
    return target instanceof Element && target.closest(TYPING) !== null;
}

/** Index of the row holding focus, or -1. */
export function currentRow(doc: Document, list: HTMLElement[] = rows(doc)): number {
    const active = doc.activeElement;

    return active instanceof Element ? list.findIndex((row) => row === active || row.contains(active)) : -1;
}

export function focusRow(list: HTMLElement[], index: number): HTMLElement | null {
    const row = list[Math.max(0, Math.min(list.length - 1, index))];

    if (row === undefined) {
        return null;
    }

    list.forEach((other) => other.removeAttribute('data-current'));
    row.setAttribute('data-current', '');
    row.focus();

    return row;
}

/** Handles one key; returns whether it was used. */
export function handleKey(event: KeyboardEvent, doc: Document = document): boolean {
    // Never behind an open dialog, nor with a modifier held.
    if (
        event.ctrlKey ||
        event.altKey ||
        event.metaKey ||
        event.defaultPrevented ||
        doc.querySelector(OPEN_DIALOG) !== null
    ) {
        return false;
    }

    if (event.key === '/') {
        if (isTyping(event.target)) {
            return false;
        }

        const search = doc.querySelector<HTMLElement>('[data-console-search]');

        if (search === null) {
            return false;
        }

        search.focus();
        event.preventDefault();

        return true;
    }

    const list = rows(doc);

    if (list.length === 0) {
        return false;
    }

    const key = event.key.toLowerCase();
    const index = currentRow(doc, list);

    if ((key === 'j' || key === 'k') && !isTyping(event.target)) {
        focusRow(list, index === -1 ? 0 : index + (key === 'j' ? 1 : -1));
        event.preventDefault();

        return true;
    }

    if (event.key === 'Enter' && index !== -1 && event.target === list[index]) {
        const button = list[index].querySelector<HTMLElement>('button, a[href]');

        if (button !== null) {
            button.focus();
            event.preventDefault();

            return true;
        }
    }

    return false;
}

export function start(doc: Document = document): () => void {
    const listener = (event: KeyboardEvent) => {
        handleKey(event, doc);
    };

    doc.addEventListener('keydown', listener);

    return () => doc.removeEventListener('keydown', listener);
}

// Vitest drives start() itself; the console page starts it on load.
if (typeof document !== 'undefined' && !import.meta.env.VITEST) {
    start();
}
