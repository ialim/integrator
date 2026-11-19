<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Filament\ConsolePageGuard;
use App\Modules\Shared\Filament\GuardedByCapability;
use App\Modules\Staff\Capabilities\CapabilityRegistry;
use App\Modules\Staff\Http\Middleware\RefusedPageStatus;
use App\Modules\Staff\Models\StaffMember;
use Filament\Facades\Filament;

/**
 * Console page and resource access from the capability registry, bound as the
 * kernel's {@see ConsolePageGuard}: a page or resource in any module declares
 * its capability with {@see NeedsCapability} and uses
 * {@see GuardedByCapability}, which asks here. The rail, the page load and
 * every Livewire call on the page then follow the registry.
 *
 * A page the signed-in member cannot use is "shown, and who can": it keeps
 * the console shell, answers 403 and names the role that can and an active
 * person who holds it, never "Access denied" or a lock.
 */
final class ConsoleAccess implements ConsolePageGuard
{
    public function __construct(private readonly CapabilityRegistry $registry) {}

    public function allows(string $capability): bool
    {
        $member = Filament::auth()->user();

        return $member instanceof StaffMember && $this->registry->allows($member, $capability);
    }

    public function refusal(string $pageTitle, string $capability): string
    {
        $member = Filament::auth()->user();

        return $this->registry->pageRefusal($pageTitle, $capability, $member instanceof StaffMember ? $member : null);
    }

    public function markRefused(): void
    {
        request()->attributes->set(RefusedPageStatus::ATTRIBUTE, true);
    }
}
