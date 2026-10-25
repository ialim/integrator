<?php

namespace App\Modules\Shared\Http;

use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::ok(['status' => 'ok']);
    }
}
