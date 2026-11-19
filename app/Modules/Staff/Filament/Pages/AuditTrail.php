<?php

namespace App\Modules\Staff\Filament\Pages;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\AuditWords;
use App\Modules\Shared\Filament\ConsoleRail;
use App\Modules\Shared\Filament\GuardedByCapability;
use App\Modules\Shared\Queries\AuditChain;
use App\Modules\Shared\Queries\AuditEntry;
use App\Modules\Shared\Queries\AuditTrail as AuditTrailQuery;
use App\Modules\Staff\Capabilities\StaffCapabilities;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use UnitEnum;

/**
 * The Audit trail page (FR-53), in the rail's Watch group, Admins only:
 * the latest rows newest first, "Look up" by an entity or actor identifier,
 * and the chain check, whose result is announced. Read-only: nothing here
 * edits, deletes, re-seals or exports a row.
 *
 * A member without `audit.view` is refused on load inside the console shell,
 * and every Livewire call (Look up, the chain check) is refused by the server
 * with 403 in words naming who can ({@see GuardedByCapability}).
 */
#[NeedsCapability(StaffCapabilities::VIEW_AUDIT)]
final class AuditTrail extends Page
{
    use GuardedByCapability;

    public const RECENT = 50;

    /** The most rows a Look up shows; the page says so when there are more. */
    public const LOOK_UP_LIMIT = 200;

    public const NOT_AN_IDENTIFIER = 'Enter an identifier like 019a2b3c-4d5e-7f60-8a9b-0c1d2e3f4a5b: a record\'s or a person\'s id, as it appears in the trail.';

    protected static ?string $slug = 'audit-trail';

    protected static ?string $title = 'Audit trail';

    protected static ?string $navigationLabel = 'Audit trail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = ConsoleRail::WATCH;

    protected static ?int $navigationSort = 10;

    protected string $view = 'staff::audit-trail';

    /** What the Admin typed into Look up. */
    public string $identifier = '';

    /** The identifier being shown, lower-cased; null shows the latest rows. */
    #[Locked]
    public ?string $lookedUp = null;

    #[Locked]
    public ?string $identifierError = null;

    /** The last chain check's result, in words. */
    #[Locked]
    public ?string $chainResult = null;

    #[Locked]
    public bool $chainBroken = false;

    public function lookUp(): void
    {
        $id = trim($this->identifier);

        if (! Str::isUuid($id)) {
            $this->identifierError = self::NOT_AN_IDENTIFIER;
            $this->lookedUp = null;

            return;
        }

        $this->identifierError = null;
        $this->lookedUp = Str::lower($id);
    }

    public function showLatest(): void
    {
        $this->identifier = '';
        $this->identifierError = null;
        $this->lookedUp = null;
    }

    public function checkChain(): void
    {
        $check = app(AuditChain::class)->check();

        $this->chainBroken = ! $check->intact;
        $this->chainResult = app(AuditWords::class)->chain($check);
    }

    /**
     * The rows to show, newest first, and whether older ones were left out.
     *
     * @return array{entries: list<AuditEntry>, more: bool, limit: int}
     */
    public function getEntries(): array
    {
        $trail = app(AuditTrailQuery::class);
        $limit = $this->lookedUp === null ? self::RECENT : self::LOOK_UP_LIMIT;
        $entries = $this->lookedUp === null ? $trail->recent($limit + 1) : $trail->lookUp($this->lookedUp, $limit + 1);

        return ['entries' => array_slice($entries, 0, $limit), 'more' => count($entries) > $limit, 'limit' => $limit];
    }

    /**
     * Which of the shown rows have a seal of their own: a row slipped in
     * behind the chain has none, whatever its sequence.
     *
     * @param  list<AuditEntry>  $entries
     * @return array<int, true>
     */
    public function sealed(array $entries): array
    {
        return app(AuditChain::class)->sealedAmong(array_map(fn (AuditEntry $entry): int => $entry->sequence, $entries));
    }
}
