<?php

declare(strict_types=1);

namespace App\Modules\Shared\Formats;

use InvalidArgumentException;

/** A value a formatter was asked to show is not a value of that kind; nothing is printed. */
final class InvalidFormatInput extends InvalidArgumentException {}
