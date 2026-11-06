<?php

namespace App\Modules\Shared\Providers;

/**
 * What a provider fake does on one call (TC-3).
 */
enum ScriptedOutcome: string
{
    case Success = 'success';
    case Refused = 'refused';
    case Timeout = 'timeout';
    case Error = 'error';
}
