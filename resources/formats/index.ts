/*
 * The one set of formatters for the Integrator app and the staff console's
 * scripts: every row of the Formats table in EXPERIENCE.md (UX-DR32). The PHP
 * twin is app/Modules/Shared/Formats/Format.php; both are tested against
 * resources/formats/cases.json so they cannot drift.
 *
 * Nothing here uses Intl or the phone's zone: West Africa Time is UTC+1 all
 * year, so the arithmetic is done by hand and reads the same everywhere.
 * Invalid input throws a FormatError; nothing ever prints a wrong value.
 */

export class FormatError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'FormatError';
    }
}

const MINUS = '−';
const NAIRA = '₦';
const DOT = '•';
const WAT_OFFSET_MS = 60 * 60 * 1000;
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** Read-aloud codes: capitals and digits with no look-alikes (no 0, 1, I, L or O). */
const READABLE = /^[2-9ABCDEFGHJKMNPQRSTUVWXYZ]+$/;

const REFERENCE_PREFIXES = {
    order: 'INT',
    creditNote: 'CN',
    case: 'C',
    returnRequest: 'RR',
    business: 'BA',
    profile: 'P',
    auditEntry: 'L',
} as const;

export type ReferenceKind = keyof typeof REFERENCE_PREFIXES;

export type PersonForm = 'team' | 'console' | 'document';

/** Units written straight after the number, as the maker writes them ("4MP"). */
const UNSPACED_UNITS = new Set(['MP']);

/** ASCII whitespace only: JavaScript's and PHP's ideas of Unicode space differ. */
const SPACE = '[ \t\n\r\f\v]';
const EDGE_SPACE = new RegExp(`^${SPACE}+|${SPACE}+$`, 'g');

function asciiTrim(text: string): string {
    return text.replace(EDGE_SPACE, '');
}

/** Codes, numbers and serials are printable ASCII and ASCII spaces; anything else is refused, never guessed at. */
function asciiOnly(raw: unknown, what: string): string {
    if (typeof raw !== 'string') {
        throw new FormatError(`${what} must be text.`);
    }

    if (/[^\t\n\v\f\r -~]/.test(raw)) {
        throw new FormatError(
            `"${raw}" is not ${what.toLowerCase()}: it holds a character outside plain printable ASCII.`,
        );
    }

    return raw;
}

function wholeNumber(value: unknown, what: string): number {
    if (typeof value !== 'number' || !Number.isSafeInteger(value)) {
        throw new FormatError(`${what} must be a whole number, got ${String(value)}.`);
    }

    return value;
}

function grouped(digits: string): string {
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/** Minor units (kobo, cents) as "1,234.50", with the sign kept apart. */
function twoDecimals(minor: number): { sign: string; figure: string } {
    const sign = minor < 0 ? MINUS : '';
    const absolute = Math.abs(minor);
    const whole = Math.floor(absolute / 100);
    const fraction = absolute % 100;

    return { sign, figure: `${grouped(String(whole))}.${String(fraction).padStart(2, '0')}` };
}

/** "₦731,729.94", a reduction "−₦38,250.00". Takes whole kobo. */
export function money(kobo: number): string {
    const { sign, figure } = twoDecimals(wholeNumber(kobo, 'An amount in kobo'));

    return `${sign}${NAIRA}${figure}`;
}

/** Console only, for supplier costs: "USD 37.21". Takes whole cents. */
export function usd(cents: number): string {
    const { sign, figure } = twoDecimals(wholeNumber(cents, 'An amount in cents'));

    return `${sign}USD ${figure}`;
}

/** Console only: "₦1,368.00 to the dollar". Takes whole kobo per dollar. */
export function rate(koboPerDollar: number): string {
    const kobo = wholeNumber(koboPerDollar, 'A rate in kobo');

    if (kobo <= 0) {
        throw new FormatError('A rate must be more than zero.');
    }

    return `${money(kobo)} to the dollar`;
}

/** "2%", "2.5%", "12.34%". Takes basis points (hundredths of a percent). */
export function percent(basisPoints: number): string {
    const points = wholeNumber(basisPoints, 'A percentage in basis points');
    const sign = points < 0 ? MINUS : '';
    const absolute = Math.abs(points);
    const whole = Math.floor(absolute / 100);
    const fraction = String(absolute % 100)
        .padStart(2, '0')
        .replace(/0+$/, '');

    return `${sign}${grouped(String(whole))}${fraction === '' ? '' : `.${fraction}`}%`;
}

function instantOf(value: unknown, what: string): Date {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        throw new FormatError(`${what} must be a valid date.`);
    }

    return value;
}

/** The wall clock in West Africa Time, read through the UTC getters. */
function wat(instant: Date): Date {
    return new Date(instant.getTime() + WAT_OFFSET_MS);
}

function dayMonth(local: Date): string {
    return `${local.getUTCDate()} ${MONTHS[local.getUTCMonth()]}`;
}

function datePart(instant: Date, now: Date, inDocument: boolean): string {
    const local = wat(instant);

    if (inDocument || local.getUTCFullYear() !== wat(now).getUTCFullYear()) {
        return `${dayMonth(local)} ${local.getUTCFullYear()}`;
    }

    return `${DAYS[local.getUTCDay()]} ${dayMonth(local)}`;
}

/** "Wed 7 Oct" this year; "7 Oct 2026" in another year or in a document. */
export function date(instant: Date, now: Date, inDocument = false): string {
    return datePart(instantOf(instant, 'The date'), instantOf(now, 'Now'), inDocument);
}

/** "Mon 5 Oct to Sun 11 Oct"; both ends carry the year when either is not this year. */
export function dateRange(start: Date, end: Date, now: Date): string {
    const from = instantOf(start, 'The start');
    const to = instantOf(end, 'The end');
    const current = instantOf(now, 'Now');

    if (to.getTime() < from.getTime()) {
        throw new FormatError('A range cannot end before it starts.');
    }

    const thisYear = wat(current).getUTCFullYear();
    const withYear = wat(from).getUTCFullYear() !== thisYear || wat(to).getUTCFullYear() !== thisYear;

    return `${datePart(from, current, withYear)} to ${datePart(to, current, withYear)}`;
}

/** "11:24 am" in West Africa Time; "11:24 am WAT" on settings and exports. */
export function time(instant: Date, withZone = false): string {
    const local = wat(instantOf(instant, 'The time'));
    const hours = local.getUTCHours();
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;
    const minutes = String(local.getUTCMinutes()).padStart(2, '0');

    return `${hour12}:${minutes} ${hours < 12 ? 'am' : 'pm'}${withZone ? ' WAT' : ''}`;
}

/**
 * "Fri 9 Oct, 4:10 pm"; "9 Oct 2027, 4:10 pm" in another year. In a document
 * or an email, read away from the app: "6 Nov 2026, 10:00 am West Africa Time".
 */
export function dateTime(instant: Date, now: Date, inDocument = false): string {
    const when = `${date(instant, now, inDocument)}, ${time(instant)}`;

    return inDocument ? `${when} West Africa Time` : when;
}

/** "3 h 12 min", "47 h", "12 min", "under 1 min". Takes whole seconds still to run. */
export function timeLeft(seconds: number): string {
    const left = wholeNumber(seconds, 'Time left in seconds');

    if (left <= 0) {
        throw new FormatError('Time left must be more than zero; a clock that has run out says so in words.');
    }

    const totalMinutes = Math.floor(left / 60);

    if (totalMinutes === 0) {
        return 'under 1 min';
    }

    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours === 0) {
        return `${minutes} min`;
    }

    return minutes === 0 ? `${hours} h` : `${hours} h ${minutes} min`;
}

function counted(count: number, one: string, many: string): string {
    const n = wholeNumber(count, 'A count');

    if (n < 0) {
        throw new FormatError('A count cannot be negative.');
    }

    return `${n} ${n === 1 ? one : many}`;
}

/** "2 business hours", "1 business hour". */
export function businessHours(count: number): string {
    return counted(count, 'business hour', 'business hours');
}

/** "1 business day", "3 business days". */
export function businessDays(count: number): string {
    return counted(count, 'business day', 'business days');
}

/** "0803 555 0142", masked "0803 ••• 0142". Takes a Nigerian number, local or +234. */
export function phone(raw: string, masked = false): string {
    asciiOnly(raw, 'A phone number');
    let digits = raw.replace(/[ \t\n\r\f\v\-()]/g, '');

    if (digits.startsWith('+234')) {
        digits = `0${digits.slice(4)}`;
    } else if (digits.startsWith('234') && digits.length === 13) {
        digits = `0${digits.slice(3)}`;
    }

    if (!/^0\d{10}$/.test(digits)) {
        throw new FormatError(`"${raw}" is not a Nigerian phone number.`);
    }

    const middle = masked ? DOT.repeat(3) : digits.slice(4, 7);

    return `${digits.slice(0, 4)} ${middle} ${digits.slice(7)}`;
}

/** "INT-0042", "CN-0012", "C-0231", "RR-0087"; staff-only "BA-0243", "P-1187", "L-20644". */
export function reference(kind: ReferenceKind, number: number): string {
    if (typeof kind !== 'string' || !Object.hasOwn(REFERENCE_PREFIXES, kind)) {
        throw new FormatError(`"${String(kind)}" is not a kind of reference.`);
    }

    const prefix = REFERENCE_PREFIXES[kind];

    const n = wholeNumber(number, 'A reference number');

    if (n <= 0) {
        throw new FormatError('A reference number starts at 1.');
    }

    return `${prefix}-${String(n).padStart(4, '0')}`;
}

function readable(raw: string, what: string): string {
    const characters = asciiOnly(raw, what)
        .replace(/[ \t\n\r\f\v-]/g, '')
        .toUpperCase();

    if (characters === '' || !READABLE.test(characters)) {
        throw new FormatError(
            `"${raw}" is not ${what.toLowerCase()}: it may only hold 2 to 9 and capitals other than I, L and O.`,
        );
    }

    return characters;
}

/** "7KQ4-M2XD-9PTA": groups of four, capitals, no look-alike letters. */
export function readAloudCode(raw: string): string {
    const characters = readable(raw, 'A read-aloud code');

    if (characters.length % 4 !== 0) {
        throw new FormatError(`"${raw}" does not split into groups of four.`);
    }

    return (characters.match(/.{4}/g) ?? []).join('-');
}

/** "IC-4829-QK7M": an issue code, four digits then four read-aloud characters. */
export function issueCode(raw: string): string {
    const characters = asciiOnly(raw, 'An issue code')
        .replace(/[ \t\n\r\f\v-]/g, '')
        .toUpperCase();
    const match = /^IC(\d{4})(.{4})$/.exec(characters);

    if (match === null) {
        throw new FormatError(`"${raw}" is not an issue code.`);
    }

    return `IC-${match[1]}-${readable(match[2] ?? '', 'An issue code')}`;
}

/** As the maker prints it: "NG4P-77124". Only the surrounding space is dropped. */
export function serial(raw: string): string {
    const printed = asciiTrim(asciiOnly(raw, 'A serial number'));

    if (printed === '') {
        throw new FormatError('A serial number cannot be empty.');
    }

    return printed;
}

/** "Chidi" inside a team, "Chidi O." in the console, "Chidi Okafor" on documents. */
export function person(fullName: string, form: PersonForm): string {
    if (typeof fullName !== 'string') {
        throw new FormatError('A name must be text.');
    }

    const parts = asciiTrim(fullName)
        .split(new RegExp(`${SPACE}+`))
        .filter(Boolean);
    const first = parts[0];
    const last = parts[parts.length - 1];

    if (first === undefined || last === undefined) {
        throw new FormatError('A name cannot be empty.');
    }

    switch (form) {
        case 'team':
            return first;
        case 'console':
            return parts.length === 1 ? first : `${first} ${[...last][0]?.toUpperCase()}.`;
        case 'document':
            return parts.join(' ');
        default:
            throw new FormatError(`"${String(form)}" is not a way to show a name.`);
    }
}

/** "305 m", "2 TB", "2.1 MB", "4MP": a number, a space, the unit. */
export function measure(value: number | string, unit: string): string {
    // A whole number, or a decimal written as text: a float prints differently in each language.
    const figure = typeof value === 'number' && Number.isSafeInteger(value) ? String(value) : value;

    if (typeof figure !== 'string' || !/^\d+(\.\d+)?$/.test(figure)) {
        throw new FormatError(`"${String(value)}" is not a measure.`);
    }

    const symbol = typeof unit === 'string' ? asciiTrim(unit) : '';

    if (symbol === '') {
        throw new FormatError('A measure needs its unit.');
    }

    const [whole = '', fraction] = figure.split('.');
    const number = `${grouped(whole)}${fraction === undefined ? '' : `.${fraction}`}`;

    return UNSPACED_UNITS.has(symbol) ? `${number}${symbol}` : `${number} ${symbol}`;
}
