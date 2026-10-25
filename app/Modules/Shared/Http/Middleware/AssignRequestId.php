<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Logging\RequestId;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts the request id from a well-formed `X-Request-Id` header or a new
 * UUIDv7, and echoes it on the response.
 */
final class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = RequestId::start($request->headers->get(RequestId::HEADER));

        $response = $next($request);
        $response->headers->set(RequestId::HEADER, $id);

        return $response;
    }
}
