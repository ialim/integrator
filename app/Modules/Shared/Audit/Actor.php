<?php

namespace App\Modules\Shared\Audit;

/**
 * Who did something, as the audit trail records it: a User, a staff member or
 * the system itself, with optional `acting_as` and `acting_for` ids.
 */
final class Actor
{
    public const SYSTEM = 'system';

    public const STAFF = 'staff';

    public const USER = 'user';

    private function __construct(
        public readonly string $type,
        public readonly ?string $id = null,
        public readonly ?string $actingAs = null,
        public readonly ?string $actingFor = null,
    ) {}

    public static function system(): self
    {
        return new self(self::SYSTEM);
    }

    public static function staff(string $id): self
    {
        return new self(self::STAFF, $id);
    }

    public static function user(string $id): self
    {
        return new self(self::USER, $id);
    }

    public function actingAs(?string $id): self
    {
        return new self($this->type, $this->id, $id, $this->actingFor);
    }

    public function actingFor(?string $id): self
    {
        return new self($this->type, $this->id, $this->actingAs, $id);
    }
}
