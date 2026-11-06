<?php

namespace App\Modules\Staff\Filament;

use App\Modules\Staff\Filament\Pages\AcceptInvitationPage;
use App\Modules\Staff\Filament\Pages\SignIn;
use App\Modules\Staff\Filament\Pages\Today;
use App\Modules\Staff\Http\ExtendStaffSessionController;
use App\Modules\Staff\Http\Middleware\EndStaffSessionAfter12Hours;
use App\Modules\Staff\Http\Middleware\SignOutInactiveStaff;
use App\Modules\Staff\Http\Middleware\TrackStaffSession;
use App\Modules\Staff\Models\StaffMember;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The staff console at /console (AD-18): its own `staff` guard, email and
 * password then an authenticator-app code, required for every staff member,
 * and a session that ends 12 hours after sign-in.
 */
final class ConsolePanelProvider extends PanelProvider
{
    public const GUARD = 'staff';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id(StaffMember::PANEL)
            ->path('console')
            ->authGuard(self::GUARD)
            ->login(SignIn::class)
            ->multiFactorAuthentication(
                AppAuthentication::make()->recoverable()->brandName('integrator'),
                isRequired: true,
            )
            ->brandName('integrator')
            ->darkMode(false)
            ->font('system-ui', provider: LocalFontProvider::class, preload: [])
            ->monoFont('ui-monospace', provider: LocalFontProvider::class, preload: [])
            ->serifFont('Palatino Linotype', provider: LocalFontProvider::class, preload: [])
            ->colors([
                'primary' => Color::hex(LedgerTokens::color('green')),
                'gray' => Color::hex(LedgerTokens::color('ink-secondary')),
                'danger' => Color::hex(LedgerTokens::color('error')),
                'warning' => Color::hex(LedgerTokens::color('caution')),
                'success' => Color::hex(LedgerTokens::color('good')),
                'info' => Color::hex(LedgerTokens::color('green')),
            ])
            ->viteTheme('resources/css/filament/console/theme.css')
            ->sidebarWidth('14rem')
            ->maxContentWidth(Width::Full)
            ->globalSearch(false)
            ->breadcrumbs(false)
            ->pages([Today::class])
            ->resources([StaffResource::class])
            ->renderHook(PanelsRenderHook::TOPBAR_LOGO_AFTER, fn () => ConsoleShell::title())
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => ConsoleShell::identity())
            ->renderHook(PanelsRenderHook::BODY_START, fn () => ConsoleShell::phoneNotice())
            ->renderHook(PanelsRenderHook::CONTENT_START, fn () => ConsoleShell::session())
            ->renderHook(PanelsRenderHook::SIMPLE_PAGE_START, fn () => ConsoleShell::session())
            ->renderHook(PanelsRenderHook::BODY_END, fn () => ConsoleShell::script())
            // An invitation link opens before sign-in: the invitee sets a password, then an authenticator app.
            ->routes(function (): void {
                Route::get('invitation/{token}', AcceptInvitationPage::class)->where('token', '[A-Za-z0-9]{1,128}')->name('invitation');
            })
            ->authenticatedRoutes(function (): void {
                Route::post('session/extend', ExtendStaffSessionController::class)->name('session.extend');
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                SignOutInactiveStaff::class,
                EndStaffSessionAfter12Hours::class,
                TrackStaffSession::class,
            ], isPersistent: true);
    }
}
