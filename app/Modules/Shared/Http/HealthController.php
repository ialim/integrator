<?php

namespace App\Modules\Shared\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * `/api/v1/health`: the app answers and its database answers (decision A7 of
 * the Epic 1 retrospective). The app's "not available" page, the host's
 * check and `restore.sh` all read the same answer. It starts no session
 * (see routes/api.php), so a check writes nothing. `/up` stays the plain
 * "the app answers" check.
 */
final class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error('The database is not answering.', 'database_unavailable', 503);
        }

        return ApiResponse::ok(['status' => 'ok']);
    }
}
