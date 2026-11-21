<?php

namespace App\Modules\Catalogue\Models;

use App\Modules\Shared\Support\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

/**
 * One item of the Supplier onboarding checklist an Admin configures (FR-81):
 * its key, whether approval needs it, and its place in the list. The five
 * Story 2.2 items are inserted by the migration; Stories 2.6 and 2.9 add
 * their document and bank items as more rows. Changed only through
 * SetChecklistItemRequired, never an `.env` entry.
 *
 * @property string $id
 * @property string $key
 * @property bool $required
 * @property int $position
 */
final class OnboardingChecklistItem extends Model
{
    use HasUuidV7;

    public const BUSINESS_DETAILS = 'business_details';

    public const CONTACTS = 'contacts';

    public const RETURNS_TERMS = 'returns_terms';

    public const DOA_TERMS = 'doa_terms';

    public const COUNTERFEIT_CHARGEBACK = 'counterfeit_chargeback';

    /** The three agreement terms: always required, by the Action and by a database CHECK. */
    public const ALWAYS_REQUIRED = [self::RETURNS_TERMS, self::DOA_TERMS, self::COUNTERFEIT_CHARGEBACK];

    protected $table = 'onboarding_checklist_items';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'position' => 'integer',
        ];
    }
}
