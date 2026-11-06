<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Staff\Capabilities\CapabilityRegistry;
use App\Modules\Staff\Http\Middleware\RefusedPageStatus;
use App\Modules\Staff\Models\StaffMember;
use Filament\Facades\Filament;

/**
 * Console page and resource access from the capability registry: a page or
 * resource declares its capability with {@see NeedsCapability}, and its
 * `canAccess()` asks here. The rail, the page load and every Livewire call on
 * the page then follow the registry.
 *
 * A page the signed-in member cannot use is "shown, and who can": it keeps
 * the console shell, answers 403 and names the role that can and an active
 * person who holds it, never "Access denied" or a lock.
 */
final class ConsoleAccess
{
    /**
     * @param  class-string  $page
     */
    public static function allows(string $page): bool
    {
        $member = Filament::auth()->user();
        $registry = app(CapabilityRegistry::class);

        return $member instanceof StaffMember && $registry->allows($member, $registry->requiredBy($page));
    }

    /**
     * "The Staff page is for Admins. Ask Ada Founder."
     *
     * @param  class-string  $page
     */
    public static function refusal(string $page, string $title): string
    {
        $member = Filament::auth()->user();
        $registry = app(CapabilityRegistry::class);

        return $registry->pageRefusal($title, $registry->requiredBy($page), $member instanceof StaffMember ? $member : null);
    }

    /** The page renders its refusal inside the shell; the response goes out as 403. */
    public static function markRefused(): void
    {
        request()->attributes->set(RefusedPageStatus::ATTRIBUTE, true);
    }
}
