<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes sure every JSON object returned under /api/v1 carries `server_time`,
 * including responses not built through {@see ApiResponse}.
 */
final class AddServerTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            // Decode as objects so `{}` stays an object and nested `{}` are not re-encoded as `[]`.
            $data = $response->getData();

            if ($data instanceof stdClass && ! property_exists($data, 'server_time')) {
                $data->server_time = ApiResponse::serverTime();
                $response->setData($data);
            }
        }

        return $response;
    }
}
