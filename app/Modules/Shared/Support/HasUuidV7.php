<?php

namespace App\Modules\Shared\Support;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Use on every Eloquent model: the primary key is a UUIDv7 from {@see Id}.
 */
trait HasUuidV7
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return Id::generate();
    }

    public function isValidUniqueId($value): bool
    {
        return is_string($value) && Id::isValid($value);
    }
}
