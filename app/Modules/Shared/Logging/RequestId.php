<?php

namespace App\Modules\Shared\Logging;

use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\Context;

/**
 * The id that ties together every log line of one request, command or job.
 * It lives in hidden Context, so queued jobs carry it on, and stays out of
 * the automatic log metadata.
 */
final class RequestId
{
    public const CONTEXT_KEY = 'request_id';

    public const HEADER = 'X-Request-Id';

    public static function current(): string
    {
        $id = Context::getHidden(self::CONTEXT_KEY);

        return is_string($id) ? $id : self::start();
    }

    public static function start(?string $incoming = null): string
    {
        $id = $incoming !== null && self::isAcceptable($incoming) ? $incoming : Id::generate();

        Context::addHidden(self::CONTEXT_KEY, $id);

        return $id;
    }

    /** An incoming id is kept only if it is short and plain, so it is safe to log. */
    public static function isAcceptable(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9._-]{8,64}$/', $value) === 1;
    }
}
