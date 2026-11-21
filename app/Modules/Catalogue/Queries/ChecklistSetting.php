<?php

namespace App\Modules\Catalogue\Queries;

/**
 * One onboarding checklist item as Admin configures it: whether approval
 * needs it, and whether it may be made optional at all (the three agreement
 * terms may not).
 */
final class ChecklistSetting
{
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly string $label,
        public readonly bool $required,
        public readonly bool $alwaysRequired,
    ) {}

    /** "Required", "Always required", "Optional". */
    public function words(): string
    {
        return match (true) {
            $this->alwaysRequired => 'Always required',
            $this->required => 'Required',
            default => 'Optional',
        };
    }

    /**
     * The settings' own audit subject, for its History.
     *
     * @return array{0: string, 1: string}
     */
    public function auditSubject(): array
    {
        return [Suppliers::SETTINGS_AUDIT_SUBJECT, $this->id];
    }
}
