<?php

namespace App\Modules\Shared\Audit;

/**
 * Who did something, as the audit trail records it: a User, a staff member or
 * the system itself, optionally `acting_as` a role (an Admin using a Finance
 * capability is acting as "finance", Epic 2) and `acting_for` a person (by id).
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

    /** The role the actor is acting as, e.g. "finance"; a role, never a person. */
    public function actingAs(?string $role): self
    {
        return new self($this->type, $this->id, $role, $this->actingFor);
    }

    /** The person (by id) the actor is acting for. */
    public function actingFor(?string $id): self
    {
        return new self($this->type, $this->id, $this->actingAs, $id);
    }
}
