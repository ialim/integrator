<?php

namespace App\Modules\Shared\Http;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;
use ReflectionProperty;
use Throwable;

/**
 * Keeps bound values out of every report and log of a database error. A
 * query's bindings hold names, emails, password and token hashes, and the
 * database's own message repeats values too (PostgreSQL's DETAIL line,
 * "Key (email)=(...) already exists", and quoted input such as `invalid input
 * syntax for type uuid: "..."`). Wired into the exception handler's map, so
 * reports and rendered errors see the redacted copy: same class, SQL with
 * `?` placeholders and no bindings, the database message's first line with
 * quoted values replaced, and the original stack trace without arguments.
 * Code that catches the exception itself (to read a constraint name, say)
 * still sees the original.
 */
final class QueryExceptionRedactor
{
    public const REDACTED = '[redacted]';

    public static function redact(QueryException $e): QueryException
    {
        $previous = $e->getPrevious() ?? $e;
        $cause = new PDOException(self::firstLineWithoutValues($previous->getMessage()));
        self::copyOrigin($previous, $cause);

        if ($previous instanceof PDOException) {
            $cause->errorInfo = is_array($previous->errorInfo) ? [$previous->errorInfo[0] ?? null, $previous->errorInfo[1] ?? null, null] : null;
        }

        $class = $e::class;
        /** @var QueryException $redacted */
        $redacted = new $class($e->getConnectionName(), $e->getSql(), [], $cause, [], $e->readWriteType, true);
        self::copyOrigin($e, $redacted);

        // The constraint's name and columns are identifiers, not values.
        if ($e instanceof UniqueConstraintViolationException && $redacted instanceof UniqueConstraintViolationException) {
            $redacted->setIndex($e->index)->setColumns($e->columns);
        }

        return $redacted;
    }

    private static function firstLineWithoutValues(string $message): string
    {
        $line = trim(strtok($message, "\n") ?: '');

        // A value follows a colon in double quotes; identifiers ("constraint", "relation", "column") do not.
        return (string) preg_replace('/:\s*"[^"]*"/', ': "'.self::REDACTED.'"', $line);
    }

    /** The code, file, line and trace of $from, the trace without its arguments. */
    private static function copyOrigin(Throwable $from, Exception $to): void
    {
        $trace = array_map(function (array $frame): array {
            unset($frame['args']);

            return $frame;
        }, $from->getTrace());

        (new ReflectionProperty(Exception::class, 'code'))->setValue($to, $from->getCode());
        (new ReflectionProperty(Exception::class, 'file'))->setValue($to, $from->getFile());
        (new ReflectionProperty(Exception::class, 'line'))->setValue($to, $from->getLine());
        (new ReflectionProperty(Exception::class, 'trace'))->setValue($to, $trace);
    }
}
