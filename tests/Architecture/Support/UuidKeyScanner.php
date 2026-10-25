<?php

namespace Tests\Architecture\Support;

use App\Modules\Shared\Support\HasUuidV7;

/**
 * AD-11: every Eloquent model takes its primary key from the Shared UUIDv7
 * helper, by using the HasUuidV7 trait.
 */
final class UuidKeyScanner
{
    /**
     * @param  list<string>  $roots
     * @return list<Violation>
     */
    public function scan(array $roots): array
    {
        $violations = [];

        foreach (ClassIndex::of(SourceFiles::in($roots))->declaredModels() as $class => $declaration) {
            if (! in_array(HasUuidV7::class, $declaration['traits'], true)) {
                $violations[] = new Violation(
                    SourceFiles::relative($declaration['file']),
                    $declaration['line'],
                    "model {$class} must use ".HasUuidV7::class.' for its UUIDv7 primary key',
                );
            }
        }

        return $violations;
    }
}
