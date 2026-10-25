<?php

namespace App\Modules\Shared\Http;

use App\Modules\Shared\Contracts\Clock;
use App\Modules\Shared\Support\Iso8601;
use Illuminate\Http\JsonResponse;

/**
 * The /api/v1 envelope: snake_case keys, `server_time` on every response,
 * and errors as `{message, errors, code}`.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $body
     */
    public static function ok(array $body = [], int $status = 200): JsonResponse
    {
        return new JsonResponse(self::withServerTime($body), $status);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        return new JsonResponse(self::withServerTime([
            'message' => $message,
            'errors' => (object) $errors,
            'code' => $code,
        ]), $status);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public static function withServerTime(array $body): array
    {
        return $body + ['server_time' => self::serverTime()];
    }

    public static function serverTime(): string
    {
        return Iso8601::format(app(Clock::class)->now());
    }
}
