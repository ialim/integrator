<?php

namespace App\Modules\Shared\Http;

use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Shared\Support\Iso8601;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every exception under /api/v1 as the JSON error envelope,
 * never as an HTML page.
 */
final class ApiExceptionRenderer
{
    private const CODES = [
        400 => 'bad_request',
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        409 => 'conflict',
        419 => 'session_expired',
        422 => 'validation_failed',
        429 => 'too_many_requests',
        503 => 'service_unavailable',
    ];

    private const MESSAGES = [
        400 => 'The request could not be read.',
        401 => 'You need to sign in.',
        403 => 'You cannot do this.',
        404 => 'Not found.',
        405 => 'This method is not allowed here.',
        409 => 'This changed before your request arrived.',
        419 => 'Your session has expired.',
        429 => 'Too many requests. Try again shortly.',
        503 => 'The service is down for maintenance.',
    ];

    public static function appliesTo(Request $request): bool
    {
        return $request->is('api/v1', 'api/v1/*');
    }

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        // A deliberate response (abort with a response, form-request redirect) passes through unchanged.
        if (! self::appliesTo($request) || $e instanceof HttpResponseException) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error($e->getMessage(), 'validation_failed', $e->status, $e->errors());
        }

        // A lost race or a refused move: 409 with the current state and who last changed it.
        if ($e instanceof TransitionRefused) {
            $response = ApiResponse::error($e->reason ?? self::MESSAGES[409], 'transition_refused', 409);
            $body = (array) $response->getData(true);
            $body['current'] = [
                'subject_type' => $e->subjectType,
                'subject_id' => $e->subjectId,
                'state' => $e->current instanceof \BackedEnum ? $e->current->value : $e->current->label(),
                'last_changed_at' => $e->lastChange !== null ? Iso8601::format($e->lastChange->occurredAt) : null,
                'last_changed_by' => $e->lastChange !== null ? ['type' => $e->lastChange->actorType, 'id' => $e->lastChange->actorId] : null,
            ];

            return $response->setData($body);
        }

        $status = match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException => $e->status() ?? 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        $response = ApiResponse::error(
            self::MESSAGES[$status] ?? ($status >= 500 ? 'Something went wrong on our side.' : 'The request could not be processed.'),
            self::CODES[$status] ?? ($status >= 500 ? 'server_error' : 'error'),
            $status,
        );

        if ($e instanceof HttpExceptionInterface) {
            $response->headers->add($e->getHeaders());
        }

        return $response;
    }
}
