<?php

namespace App\Modules\Pricing\Filament;

use App\Modules\Catalogue\Actions\CreateProductRange;
use App\Modules\Catalogue\Queries\ProductRanges;
use App\Modules\Catalogue\Queries\ProductRangeSummary;
use App\Modules\Pricing\Actions\SetMarginFloor;
use App\Modules\Pricing\Capabilities\PricingCapabilities;
use App\Modules\Pricing\Queries\PricingRules;
use App\Modules\Pricing\Queries\RulePercent;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\ConsolePageGuard;
use App\Modules\Shared\Filament\ConsoleRail;
use App\Modules\Shared\Filament\GuardedByCapability;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use ReflectionClass;
use UnitEnum;

/**
 * The Pricing and rules page, in the rail's Rules group (FR-13). Its one
 * section today is "Markups and the floor": a console table captioned
 * "Platform Markup by Product Range", the margin floor, and History for each
 * range and for the floor, from the audit trail.
 *
 * Admins add, rename, flag and retire ranges (Catalogue's Actions) and change
 * a markup or the floor (Pricing's), each with a required reason, in a
 * dialog. Finance and Operations open the page read-only: the same table and
 * floor under the line "Set by an Admin. Only an Admin can change these.",
 * with no change buttons at all, and every Action refuses them on the
 * server whatever the screen shows. The buttons live in
 * {@see ManagesPricingRules}.
 */
#[NeedsCapability(PricingCapabilities::VIEW_RULES)]
final class PricingAndRules extends Page
{
    use GuardedByCapability;
    use ManagesPricingRules;

    public const READ_ONLY = 'Set by an Admin. Only an Admin can change these.';

    public const NOT_FOUND = 'That Product Range was not found. Reload the Pricing and rules page and try again.';

    protected static ?string $slug = 'pricing-and-rules';

    protected static ?string $title = 'Pricing and rules';

    protected static ?string $navigationLabel = 'Pricing and rules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = ConsoleRail::RULES;

    protected static ?int $navigationSort = 10;

    protected string $view = 'pricing::pricing-and-rules';

    /** What the last change did, or why it was refused, in words. */
    #[Locked]
    public ?string $notice = null;

    #[Locked]
    public bool $noticeIsRefusal = false;

    public function getSubheading(): string
    {
        return 'Every change asks for a reason and is audited.';
    }

    /**
     * @return list<ProductRangeSummary>
     */
    public function getRanges(): array
    {
        return app(ProductRanges::class)->all();
    }

    /**
     * @return array<string, RulePercent>
     */
    public function getMarkups(): array
    {
        return app(PricingRules::class)->markups();
    }

    public function getFloor(): RulePercent
    {
        return app(PricingRules::class)->marginFloor();
    }

    /** Whether the signed-in member may set up Product Ranges: Catalogue's Action says which capability. */
    public function canManageRanges(): bool
    {
        return self::mayCall(CreateProductRange::class);
    }

    /** Whether the signed-in member may change markups and the floor. */
    public function canManageRules(): bool
    {
        return self::mayCall(SetMarginFloor::class);
    }

    /**
     * Whether the signed-in member holds the capability $action declares.
     *
     * @param  class-string  $action
     */
    private static function mayCall(string $action): bool
    {
        $attributes = (new ReflectionClass($action))->getAttributes(NeedsCapability::class);

        return $attributes !== [] && app(ConsolePageGuard::class)->allows($attributes[0]->newInstance()->capability);
    }

    private function actor(): Model
    {
        $member = Filament::auth()->user();
        abort_unless($member instanceof Model, 403, self::refusalWords());

        return $member;
    }

    /**
     * The range id from Livewire's arguments: a UUID, lower-cased; null for anything else.
     *
     * @param  array<string, mixed>  $arguments
     */
    private static function rangeIdFrom(array $arguments): ?string
    {
        $id = $arguments['range'] ?? null;

        return is_string($id) && Str::isUuid($id) ? Str::lower($id) : null;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function rangeOf(array $arguments): ?ProductRangeSummary
    {
        $id = self::rangeIdFrom($arguments);

        return $id === null ? null : app(ProductRanges::class)->find($id);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function rangeId(array $arguments): string
    {
        return self::rangeIdFrom($arguments) ?? abort(404, self::NOT_FOUND);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function nameOf(array $arguments): string
    {
        return $this->rangeOf($arguments)->name ?? 'this Product Range';
    }

    private function say(string $notice, bool $refusal = false): void
    {
        $this->notice = $notice;
        $this->noticeIsRefusal = $refusal;
    }
}
