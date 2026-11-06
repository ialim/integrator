<?php

namespace App\Modules\Shared\Providers;

/** The provider definitively refused the call; retrying the same call fails again. */
final class ProviderRefused extends ProviderFailure {}
