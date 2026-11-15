<?php

declare(strict_types=1);

namespace App\Modules\Shared\Formats;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * The one set of formatters for the staff console, emails and documents:
 * every row of the Formats table in EXPERIENCE.md (UX-DR32). The TypeScript
 * twin is resources/formats/index.ts; both are tested against
 * resources/formats/cases.json so they cannot drift.
 *
 * Times show in West Africa Time (UTC+1 all year). Invalid input throws
 * InvalidFormatInput; nothing ever prints a wrong value.
 */
final class Format
{
    public const TIMEZONE = 'Africa/Lagos';

    private const MINUS = "\u{2212}";

    private const NAIRA = "\u{20A6}";

    private const DOT = "\u{2022}";

    /** The largest whole number JavaScript holds exactly, so both twins refuse the same amounts. */
    private const SAFE_INTEGER = 9007199254740991;

    /** Read-aloud codes: capitals and digits with no look-alikes (no 0, 1, I, L or O). */
    private const READABLE = '/^[2-9ABCDEFGHJKMNPQRSTUVWXYZ]+$/';

    private const REFERENCE_PREFIXES = [
        'order' => 'INT',
        'creditNote' => 'CN',
        'case' => 'C',
        'returnRequest' => 'RR',
        'business' => 'BA',
        'profile' => 'P',
        'auditEntry' => 'L',
    ];

    /** ASCII whitespace only: PHP's and JavaScript's ideas of Unicode space differ. */
    private const SPACE = " \t\n\r\f\x0B";

    /** Units written straight after the number, as the maker writes them ("4MP"). */
    private const UNSPACED_UNITS = ['MP'];

    /** "₦731,729.94", a reduction "−₦38,250.00". Takes whole kobo. */
    public static function money(mixed $kobo): string
    {
        [$sign, $figure] = self::twoDecimals(self::wholeNumber($kobo, 'An amount in kobo'));

        return $sign.self::NAIRA.$figure;
    }

    /** Console only, for supplier costs: "USD 37.21". Takes whole cents. */
    public static function usd(mixed $cents): string
    {
        [$sign, $figure] = self::twoDecimals(self::wholeNumber($cents, 'An amount in cents'));

        return $sign.'USD '.$figure;
    }

    /** Console only: "₦1,368.00 to the dollar". Takes whole kobo per dollar. */
    public static function rate(mixed $koboPerDollar): string
    {
        $kobo = self::wholeNumber($koboPerDollar, 'A rate in kobo');

        if ($kobo <= 0) {
            throw new InvalidFormatInput('A rate must be more than zero.');
        }

        return self::money($kobo).' to the dollar';
    }

    /** "2%", "2.5%", "12.34%". Takes basis points (hundredths of a percent). */
    public static function percent(mixed $basisPoints): string
    {
        $points = self::wholeNumber($basisPoints, 'A percentage in basis points');
        $sign = $points < 0 ? self::MINUS : '';
        $absolute = abs($points);
        $fraction = rtrim(str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT), '0');

        return $sign.self::grouped((string) intdiv($absolute, 100)).($fraction === '' ? '' : '.'.$fraction).'%';
    }

    /** "Wed 7 Oct" this year; "7 Oct 2026" in another year or in a document. */
    public static function date(mixed $instant, mixed $now, bool $inDocument = false): string
    {
        return self::datePart(self::wat($instant, 'The date'), self::wat($now, 'Now'), $inDocument);
    }

    /** "Mon 5 Oct to Sun 11 Oct"; both ends carry the year when either is not this year. */
    public static function dateRange(mixed $start, mixed $end, mixed $now): string
    {
        $from = self::wat($start, 'The start');
        $to = self::wat($end, 'The end');
        $current = self::wat($now, 'Now');

        if ($to < $from) {
            throw new InvalidFormatInput('A range cannot end before it starts.');
        }

        $thisYear = $current->format('Y');
        $withYear = $from->format('Y') !== $thisYear || $to->format('Y') !== $thisYear;

        return self::datePart($from, $current, $withYear).' to '.self::datePart($to, $current, $withYear);
    }

    /** "11:24 am" in West Africa Time; "11:24 am WAT" on settings and exports. */
    public static function time(mixed $instant, bool $withZone = false): string
    {
        return self::wat($instant, 'The time')->format('g:i a').($withZone ? ' WAT' : '');
    }

    /**
     * "Fri 9 Oct, 4:10 pm"; "9 Oct 2027, 4:10 pm" in another year. In a document
     * or an email, read away from the app: "6 Nov 2026, 10:00 am West Africa Time".
     */
    public static function dateTime(mixed $instant, mixed $now, bool $inDocument = false): string
    {
        $when = self::date($instant, $now, $inDocument).', '.self::time($instant);

        return $inDocument ? $when.' West Africa Time' : $when;
    }

    /** "3 h 12 min", "47 h", "12 min", "under 1 min". Takes whole seconds still to run. */
    public static function timeLeft(mixed $seconds): string
    {
        $left = self::wholeNumber($seconds, 'Time left in seconds');

        if ($left <= 0) {
            throw new InvalidFormatInput('Time left must be more than zero; a clock that has run out says so in words.');
        }

        $totalMinutes = intdiv($left, 60);

        if ($totalMinutes === 0) {
            return 'under 1 min';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours === 0) {
            return "{$minutes} min";
        }

        return $minutes === 0 ? "{$hours} h" : "{$hours} h {$minutes} min";
    }

    /** "2 business hours", "1 business hour". */
    public static function businessHours(mixed $count): string
    {
        return self::counted($count, 'business hour', 'business hours');
    }

    /** "1 business day", "3 business days". */
    public static function businessDays(mixed $count): string
    {
        return self::counted($count, 'business day', 'business days');
    }

    /** "0803 555 0142", masked "0803 ••• 0142". Takes a Nigerian number, local or +234. */
    public static function phone(mixed $raw, bool $masked = false): string
    {
        $raw = self::asciiOnly($raw, 'a phone number');
        $digits = (string) preg_replace('/[ \t\n\r\f\x0B\-()]/', '', $raw);

        if (str_starts_with($digits, '+234')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '234') && strlen($digits) === 13) {
            $digits = '0'.substr($digits, 3);
        }

        if (preg_match('/^0\d{10}$/', $digits) !== 1) {
            throw new InvalidFormatInput("\"{$raw}\" is not a Nigerian phone number.");
        }

        $middle = $masked ? str_repeat(self::DOT, 3) : substr($digits, 4, 3);

        return substr($digits, 0, 4).' '.$middle.' '.substr($digits, 7);
    }

    /** "INT-0042", "CN-0012", "C-0231", "RR-0087"; staff-only "BA-0243", "P-1187", "L-20644". */
    public static function reference(mixed $kind, mixed $number): string
    {
        if (! is_string($kind) || ! array_key_exists($kind, self::REFERENCE_PREFIXES)) {
            throw new InvalidFormatInput('"'.(is_scalar($kind) ? $kind : gettype($kind)).'" is not a kind of reference.');
        }

        $n = self::wholeNumber($number, 'A reference number');

        if ($n <= 0) {
            throw new InvalidFormatInput('A reference number starts at 1.');
        }

        return self::REFERENCE_PREFIXES[$kind].'-'.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /** "7KQ4-M2XD-9PTA": groups of four, capitals, no look-alike letters. */
    public static function readAloudCode(mixed $raw): string
    {
        $characters = self::readable($raw, 'a read-aloud code');

        if (strlen($characters) % 4 !== 0) {
            throw new InvalidFormatInput("\"{$characters}\" does not split into groups of four.");
        }

        return implode('-', str_split($characters, 4));
    }

    /** "IC-4829-QK7M": an issue code, four digits then four read-aloud characters. */
    public static function issueCode(mixed $raw): string
    {
        $raw = self::asciiOnly($raw, 'an issue code');
        $characters = strtoupper((string) preg_replace('/[ \t\n\r\f\x0B-]/', '', $raw));

        if (preg_match('/^IC(\d{4})(.{4})$/', $characters, $match) !== 1) {
            throw new InvalidFormatInput("\"{$raw}\" is not an issue code.");
        }

        return 'IC-'.$match[1].'-'.self::readable($match[2], 'an issue code');
    }

    /** As the maker prints it: "NG4P-77124". Only the surrounding space is dropped. */
    public static function serial(mixed $raw): string
    {
        $printed = trim(self::asciiOnly($raw, 'a serial number'), self::SPACE);

        if ($printed === '') {
            throw new InvalidFormatInput('A serial number cannot be empty.');
        }

        return $printed;
    }

    /** "Chidi" inside a team, "Chidi O." in the console, "Chidi Okafor" on documents. */
    public static function person(mixed $fullName, mixed $form): string
    {
        if (! is_string($fullName)) {
            throw new InvalidFormatInput('A name must be text.');
        }

        $parts = preg_split('/[ \t\n\r\f\x0B]+/', trim($fullName, self::SPACE), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            throw new InvalidFormatInput('A name cannot be empty.');
        }

        $first = $parts[0];
        $last = $parts[count($parts) - 1];

        return match ($form) {
            'team' => $first,
            'console' => count($parts) === 1 ? $first : $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.',
            'document' => implode(' ', $parts),
            default => throw new InvalidFormatInput('"'.(is_scalar($form) ? $form : gettype($form)).'" is not a way to show a name.'),
        };
    }

    /** "305 m", "2 TB", "2.1 MB", "4MP": a number, a space, the unit. */
    public static function measure(mixed $value, mixed $unit): string
    {
        // A whole number, or a decimal written as text: a float prints differently in each language.
        $figure = is_int($value) && abs($value) <= self::SAFE_INTEGER ? (string) $value : $value;

        if (! is_string($figure) || preg_match('/^\d+(\.\d+)?$/', $figure) !== 1) {
            throw new InvalidFormatInput('"'.(is_scalar($value) ? $value : gettype($value)).'" is not a measure.');
        }

        if (! is_string($unit) || trim($unit, self::SPACE) === '') {
            throw new InvalidFormatInput('A measure needs its unit.');
        }

        $pieces = explode('.', $figure, 2);
        $number = self::grouped($pieces[0]).(isset($pieces[1]) ? '.'.$pieces[1] : '');
        $unit = trim($unit, self::SPACE);

        return in_array($unit, self::UNSPACED_UNITS, true) ? $number.$unit : $number.' '.$unit;
    }

    /** Codes, numbers and serials are printable ASCII and ASCII spaces; anything else is refused, never guessed at. */
    private static function asciiOnly(mixed $raw, string $what): string
    {
        if (! is_string($raw)) {
            throw new InvalidFormatInput("{$what} must be text.");
        }

        if (preg_match('/[^\t\n\x0B\f\r -~]/', $raw) === 1) {
            throw new InvalidFormatInput("\"{$raw}\" is not {$what}: it holds a character outside plain printable ASCII.");
        }

        return $raw;
    }

    private static function wholeNumber(mixed $value, string $what): int
    {
        if (! is_int($value) || abs($value) > self::SAFE_INTEGER) {
            throw new InvalidFormatInput("{$what} must be a whole number, got ".var_export($value, true).'.');
        }

        return $value;
    }

    private static function grouped(string $digits): string
    {
        return (string) preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $digits);
    }

    /** @return array{0: string, 1: string} the sign, and the figure as "1,234.50" */
    private static function twoDecimals(int $minor): array
    {
        $absolute = abs($minor);

        return [
            $minor < 0 ? self::MINUS : '',
            self::grouped((string) intdiv($absolute, 100)).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT),
        ];
    }

    private static function wat(mixed $instant, string $what): DateTimeImmutable
    {
        if (! $instant instanceof DateTimeInterface) {
            throw new InvalidFormatInput("{$what} must be a date.");
        }

        return DateTimeImmutable::createFromInterface($instant)->setTimezone(new DateTimeZone(self::TIMEZONE));
    }

    private static function datePart(DateTimeImmutable $local, DateTimeImmutable $now, bool $withYear): string
    {
        if ($withYear || $local->format('Y') !== $now->format('Y')) {
            return $local->format('j M Y');
        }

        return $local->format('D j M');
    }

    private static function counted(mixed $count, string $one, string $many): string
    {
        $n = self::wholeNumber($count, 'A count');

        if ($n < 0) {
            throw new InvalidFormatInput('A count cannot be negative.');
        }

        return $n.' '.($n === 1 ? $one : $many);
    }

    /** Text of 2 to 9 and capitals other than I, L and O, with spaces and dashes dropped. */
    private static function readable(mixed $raw, string $what): string
    {
        $raw = self::asciiOnly($raw, $what);
        $characters = strtoupper((string) preg_replace('/[ \t\n\r\f\x0B-]/', '', $raw));

        if (preg_match(self::READABLE, $characters) !== 1) {
            throw new InvalidFormatInput("\"{$raw}\" is not {$what}: it may only hold 2 to 9 and capitals other than I, L and O.");
        }

        return $characters;
    }
}
