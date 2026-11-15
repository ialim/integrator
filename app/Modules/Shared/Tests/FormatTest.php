<?php

use App\Modules\Shared\Formats\Format;
use App\Modules\Shared\Formats\InvalidFormatInput;

/*
 * The shared cases (resources/formats/cases.json), which the TypeScript twin
 * (resources/formats/index.ts) runs too, so the two cannot drift.
 */

/** @return list<array{fn: string, args: list<mixed>, out?: string, throws?: bool}> */
function sharedFormatCases(): array
{
    $json = json_decode((string) file_get_contents(base_path('resources/formats/cases.json')), true, flags: JSON_THROW_ON_ERROR);

    return $json['cases'];
}

/** ISO 8601 instants become dates; a date function given other text gets that text, and must refuse it. */
function formatArgument(mixed $value): mixed
{
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1) {
        return new DateTimeImmutable($value);
    }

    return $value;
}

it('has a case for every formatter, and a formatter for every case', function () {
    $methods = array_map(
        fn (ReflectionMethod $method) => $method->getName(),
        (new ReflectionClass(Format::class))->getMethods(ReflectionMethod::IS_PUBLIC),
    );
    $covered = array_values(array_unique(array_column(sharedFormatCases(), 'fn')));
    sort($methods);
    sort($covered);

    expect($covered)->toBe($methods);
});

it('formats each shared case exactly, or refuses it', function (string $fn, array $args, ?string $out, bool $throws) {
    $call = fn () => Format::$fn(...array_map(formatArgument(...), $args));

    if ($throws) {
        expect($call)->toThrow(InvalidFormatInput::class);
    } else {
        expect($call())->toBe($out);
    }
})->with(function () {
    foreach (sharedFormatCases() as $case) {
        yield $case['fn'].'('.json_encode($case['args'], JSON_UNESCAPED_UNICODE).')' => [
            $case['fn'], $case['args'], $case['out'] ?? null, $case['throws'] ?? false,
        ];
    }
});
