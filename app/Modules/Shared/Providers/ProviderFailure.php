<?php

namespace App\Modules\Shared\Providers;

use RuntimeException;

/**
 * A provider call that did not succeed (AD-15). Every adapter and every fake
 * reports failure as one of three kinds, so callers and tests treat all
 * providers alike: {@see ProviderRefused} (definitive: the same call will fail
 * again), {@see ProviderTimedOut} (no answer in time: the call may or may not
 * have taken effect) and {@see ProviderErrored} (any other failure, worth a
 * retry). Messages never carry personal values.
 */
abstract class ProviderFailure extends RuntimeException {}
