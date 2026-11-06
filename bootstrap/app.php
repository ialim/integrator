<?php

use App\Modules\Shared\Http\ApiExceptionRenderer;
use App\Modules\Shared\Http\Middleware\AddServerTime;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use App\Modules\Shared\Http\QueryExceptionRedactor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);
        $middleware->api(append: [AddServerTime::class]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Database errors are reported and rendered without their bound values.
        $exceptions->map(QueryException::class, fn (QueryException $e): QueryException => QueryExceptionRedactor::redact($e));

        $exceptions->render(fn (Throwable $e, Request $request) => ApiExceptionRenderer::render($e, $request));
    })->create();
