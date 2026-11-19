<?php

use App\Modules\Pricing\Filament\PricingAndRules;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use App\Modules\Shared\Filament\GuardedByCapability;
use App\Modules\Staff\Capabilities\CapabilityRegistry;
use App\Modules\Staff\Filament\Pages\AcceptInvitationPage;
use App\Modules\Staff\Filament\Pages\SignIn;
use App\Modules\Staff\Filament\Pages\Today;
use App\Modules\Staff\Filament\StaffResource;
use App\Modules\Staff\Filament\StaffResource\ListStaff;
use App\Modules\Staff\Models\StaffMember;
use Filament\Auth\MultiFactor\Pages\SetUpRequiredMultiFactorAuthentication;
use Filament\Facades\Filament;
use Filament\Pages\BasePage;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Support\Facades\Route;

/*
 * The source scan checks files under app/Modules; this checks what the
 * console panel actually serves: every page behind a console route and every
 * registered resource, wherever its class lives.
 */

/** Filament's own pages, which have no capability of their own: its MFA set-up. */
const FILAMENT_OWN_PAGES = [
    SetUpRequiredMultiFactorAuthentication::class,
];

/**
 * @return list<string> a problem per class, naming it
 */
function consolePanelProblems(): array
{
    $panel = Filament::getPanel(StaffMember::PANEL);
    $registry = app(CapabilityRegistry::class);
    $classes = $panel->getResources();

    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();
        $class = $route->getControllerClass();

        if (str_starts_with($name, 'filament.'.StaffMember::PANEL.'.') && is_string($class) && is_subclass_of($class, BasePage::class)) {
            $classes[] = $class;
        }
    }

    $problems = [];

    foreach (array_values(array_unique($classes)) as $class) {
        if (in_array($class, FILAMENT_OWN_PAGES, true)) {
            continue;
        }

        $declarer = is_subclass_of($class, ResourcePage::class) ? $class::getResource() : $class;
        $needs = (new ReflectionClass($declarer))->getAttributes(NeedsCapability::class);
        $none = (new ReflectionClass($declarer))->getAttributes(NeedsNoCapability::class);
        $guarded = in_array(GuardedByCapability::class, class_uses_recursive($class), true);

        if ($needs === [] && $none === []) {
            $problems[] = "{$class} is served by the console but declares no capability";
        } elseif ($needs !== [] && ! $registry->has($needs[0]->newInstance()->capability)) {
            $problems[] = "{$class} needs a capability the registry does not know";
        } elseif ($needs !== [] && ! $guarded) {
            $problems[] = "{$class} declares a capability but does not use GuardedByCapability";
        }
    }

    return $problems;
}

it('declares a capability on every page and resource the console panel serves, naming any that does not', function () {
    $problems = consolePanelProblems();

    expect($problems)->toBeEmpty("The console serves classes without a checked capability:\n  ".implode("\n  ", $problems));
});

it('walks the Staff page, Today, sign-in and invitation pages, and the pages other modules add', function () {
    $panel = Filament::getPanel(StaffMember::PANEL);
    $served = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'filament.'.StaffMember::PANEL.'.'))
        ->map(fn ($route) => $route->getControllerClass())
        ->filter()
        ->values()
        ->all();

    expect($served)->toContain(Today::class)
        ->toContain(SignIn::class)
        ->toContain(AcceptInvitationPage::class)
        ->toContain(ListStaff::class)
        ->toContain(PricingAndRules::class)
        ->and($panel->getResources())->toContain(StaffResource::class);
});
