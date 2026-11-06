<?php

namespace App\Modules\Staff\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A console page that refused the signed-in member still renders inside the
 * console shell, naming who can; this sends that page with status 403, so the
 * refusal is the server's, not only the screen's.
 */
final class RefusedPageStatus
{
    public const ATTRIBUTE = 'console.page_refused';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request->attributes->get(self::ATTRIBUTE) === true && $response->isSuccessful()) {
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        return $response;
    }
}
