<?php

namespace App\Modules\Staff\Actions;

use RuntimeException;

final class FirstAdminRefused extends RuntimeException
{
    public static function staffExist(): self
    {
        return new self('A staff account already exists, so no first Admin was created. An Admin adds further staff from the console.');
    }
}
