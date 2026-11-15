import { describe, expect, it } from 'vitest';
import * as format from './index';
import { FormatError } from './index';
import shared from './cases.json';

/*
 * The shared cases (resources/formats/cases.json), which the PHP twin
 * (app/Modules/Shared/Formats/Format.php) runs too, so the two cannot drift.
 */
type Case = { fn: string; args: unknown[]; out?: string; throws?: boolean };

const ISO_INSTANT = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/;
const DATE_FUNCTIONS = new Set(['date', 'dateRange', 'time', 'dateTime']);

function argument(fn: string, value: unknown): unknown {
    if (typeof value === 'string' && ISO_INSTANT.test(value)) {
        return new Date(value);
    }

    // A date function given text that is not an instant gets an invalid date.
    if (DATE_FUNCTIONS.has(fn) && typeof value === 'string') {
        return new Date(Number.NaN);
    }

    return value;
}

const formatters = format as unknown as Record<string, (...args: unknown[]) => string>;
const cases = shared.cases as Case[];

describe('the shared formats', () => {
    it('has a case for every formatter, and a formatter for every case', () => {
        const exported = Object.keys(format).filter((name) => name !== 'FormatError');
        const covered = new Set(cases.map((c) => c.fn));

        expect([...covered].sort()).toEqual(exported.sort());
    });

    it.each(cases.map((c) => [`${c.fn}(${JSON.stringify(c.args)})`, c] as const))('%s', (_name, c) => {
        const formatter = formatters[c.fn];
        expect(formatter, `no formatter named ${c.fn}`).toBeTypeOf('function');

        const call = () => formatter?.(...c.args.map((value) => argument(c.fn, value)));

        if (c.throws === true) {
            expect(call).toThrow(FormatError);
        } else {
            expect(call()).toBe(c.out);
        }
    });
});
