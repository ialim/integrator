<?php

use App\Modules\Shared\Contracts\Clock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;

beforeEach(fn () => app(Clock::class)->set('2026-10-25T09:00:00Z'));
afterEach(fn () => app(Clock::class)->reset());

it('answers the health check with status and server time', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'server_time' => '2026-10-25T09:00:00Z']);
});

it('answers an unknown /api/v1 route with the JSON error envelope, never HTML', function () {
    $response = $this->get('/api/v1/no-such-thing', ['Accept' => 'text/html']);

    $response->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson([
            'message' => 'Not found.',
            'errors' => [],
            'code' => 'not_found',
            'server_time' => '2026-10-25T09:00:00Z',
        ]);
});

it('answers a validation error with field errors and the validation_failed code', function () {
    Route::middleware('api')->post('/api/v1/_probe/validate', function () {
        request()->validate(['quantity' => ['required', 'integer']]);
    });

    $this->postJson('/api/v1/_probe/validate', [])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonPath('server_time', '2026-10-25T09:00:00Z')
        ->assertJsonStructure(['message', 'errors' => ['quantity'], 'code', 'server_time']);
});

it('hides the detail of an unexpected error behind the server_error code', function () {
    Route::middleware('api')->get('/api/v1/_probe/explode', fn () => throw new RuntimeException('secret detail'));

    $this->getJson('/api/v1/_probe/explode')
        ->assertStatus(500)
        ->assertJsonPath('code', 'server_error')
        ->assertJsonMissing(['message' => 'secret detail']);
});

it('adds server_time to any JSON object returned under /api/v1', function () {
    Route::middleware('api')->get('/api/v1/_probe/plain', fn () => response()->json(['note' => 'plain']));

    $this->getJson('/api/v1/_probe/plain')
        ->assertExactJson(['note' => 'plain', 'server_time' => '2026-10-25T09:00:00Z']);
});

it('adds server_time to an empty object and keeps nested empty objects as objects', function () {
    Route::middleware('api')->get('/api/v1/_probe/empty', fn () => response()->json(new stdClass));
    Route::middleware('api')->get('/api/v1/_probe/nested', fn () => response()->json(['meta' => new stdClass]));

    expect($this->getJson('/api/v1/_probe/empty')->getContent())->toBe('{"server_time":"2026-10-25T09:00:00Z"}')
        ->and($this->getJson('/api/v1/_probe/nested')->getContent())->toBe('{"meta":{},"server_time":"2026-10-25T09:00:00Z"}');
});

it('answers each client error with its status, code and server_time', function (string $method, string $uri, ?Closure $throw, int $status, string $code, array $headers) {
    if ($throw !== null) {
        Route::middleware('api')->get($uri, $throw);
    }

    $response = $this->json($method, $uri)
        ->assertStatus($status)
        ->assertJsonPath('code', $code)
        ->assertJsonPath('server_time', '2026-10-25T09:00:00Z');

    foreach ($headers as $name => $value) {
        $response->assertHeader($name, $value);
    }
})->with([
    '401' => ['GET', '/api/v1/_probe/401', fn () => throw new AuthenticationException, 401, 'unauthenticated', []],
    '403' => ['GET', '/api/v1/_probe/403', fn () => throw new AuthorizationException, 403, 'forbidden', []],
    '419' => ['GET', '/api/v1/_probe/419', fn () => abort(419), 419, 'session_expired', []],
    '429' => ['GET', '/api/v1/_probe/429', fn () => throw new ThrottleRequestsException('Slow down.', null, ['Retry-After' => '30']), 429, 'too_many_requests', ['Retry-After' => '30']],
    '405' => ['POST', '/api/v1/health', null, 405, 'method_not_allowed', ['Allow' => 'GET, HEAD']],
]);

it('passes a HttpResponseException response through unchanged', function () {
    Route::middleware('api')->get('/api/v1/_probe/response', fn () => throw new HttpResponseException(
        response()->json(['custom' => true, 'server_time' => 'kept'], 418),
    ));

    $this->getJson('/api/v1/_probe/response')
        ->assertStatus(418)
        ->assertExactJson(['custom' => true, 'server_time' => 'kept']);
});

it('serves the SPA shell on app paths', function () {
    $this->withoutVite();

    $this->get('/projects/123')
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);

    $this->get('/consoles')->assertOk()->assertSee('<div id="app"></div>', false);
    $this->get('/api')->assertNotFound();
    $this->get('/console')->assertNotFound();
});
