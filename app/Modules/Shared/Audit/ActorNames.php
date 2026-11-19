<?php

namespace App\Modules\Shared\Audit;

/**
 * Names for the people an audit row mentions, so the shared History view and
 * the Audit trail page can say "Ada Founder" without importing `Staff`
 * (AD-2). Staff binds it; an id it does not know is left out.
 */
interface ActorNames
{
    /**
     * @param  list<string>  $ids
     * @return array<string, string> id => name, for the ids that name a staff member
     */
    public function namesOf(array $ids): array;
}
