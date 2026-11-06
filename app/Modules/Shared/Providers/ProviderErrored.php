<?php

namespace App\Modules\Shared\Providers;

/** The provider call failed for another reason (an outage, a 5xx); worth a retry. */
final class ProviderErrored extends ProviderFailure {}
