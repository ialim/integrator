<?php

use App\Modules\Shared\Logging\RequestId;
use App\Modules\Shared\Support\Id;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->logFile = storage_path('logs/test-'.Id::generate().'.log');
    config(['logging.channels.json.path' => $this->logFile, 'logging.default' => 'json']);
    Log::forgetChannel('json');

    Route::middleware('api')->get('/api/v1/_probe/log', function () {
        Log::info('probe line', ['order' => 'ref-1']);

        return ['logged' => true];
    });
});

afterEach(fn () => @unlink($this->logFile));

/** @return list<array<string, mixed>> */
function jsonLines(string $file): array
{
    return array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

it('writes JSON log lines carrying the incoming request id, and echoes it', function () {
    $this->getJson('/api/v1/_probe/log', [RequestId::HEADER => 'req-0123456789'])
        ->assertOk()
        ->assertHeader(RequestId::HEADER, 'req-0123456789');

    $lines = jsonLines($this->logFile);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]['message'])->toBe('probe line')
        ->and($lines[0]['context'])->toBe(['order' => 'ref-1'])
        ->and($lines[0]['extra']['request_id'])->toBe('req-0123456789');
});

it('assigns a UUIDv7 request id when the header is missing or unsafe', function () {
    $response = $this->getJson('/api/v1/_probe/log', [RequestId::HEADER => "bad id\nwith newline"]);

    $id = $response->headers->get(RequestId::HEADER);

    expect(Id::isValid((string) $id))->toBeTrue()
        ->and(jsonLines($this->logFile)[0]['extra']['request_id'])->toBe($id);
});
