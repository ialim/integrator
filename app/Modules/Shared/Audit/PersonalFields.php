<?php

namespace App\Modules\Shared\Audit;

/**
 * Each module's list of personal or secret columns, by table. The audit writer
 * reads it and records a change to such a column only as "changed", never its
 * value (a NIN, a name, an email, a password hash, a second-factor secret).
 *
 * Modules declare their columns from their service provider; the registry is a
 * container singleton.
 */
final class PersonalFields
{
    public const CHANGED = 'changed';

    /** @var array<string, list<string>> table => columns */
    private array $columns = [];

    /**
     * @param  list<string>  $columns
     */
    public function declare(string $table, array $columns): void
    {
        $this->columns[$table] = array_values(array_unique([...($this->columns[$table] ?? []), ...$columns]));
    }

    public function isPersonal(string $table, string $column): bool
    {
        return in_array($column, $this->columns[$table] ?? [], true);
    }

    /**
     * @return list<string>
     */
    public function of(string $table): array
    {
        return $this->columns[$table] ?? [];
    }
}
