<?php

namespace App\Modules\Staff\Filament\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * Step one (email and password) and step two (the authenticator code) of the
 * staff sign-in. There is no "remember me": a staff session lasts 12 hours.
 * Throttling is Filament's: 5 sign-in attempts a minute, and 5 wrong codes.
 * A throttled attempt is reported in words beside the field, not in a toast.
 */
final class SignIn extends Login
{
    /** Why the last session ended, shown once above the form. */
    #[Locked]
    public ?string $endedMessage = null;

    public function mount(): void
    {
        $ended = session('staff_session.ended');
        $this->endedMessage = is_string($ended) ? $ended : null;

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make(fn (): HtmlString => new HtmlString('<span role="status" class="console-notice">'.e((string) $this->endedMessage).'</span>'))
                ->visible(fn (): bool => filled($this->endedMessage) && blank($this->userUndertakingMultiFactorAuthentication)),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            $this->getFormContentComponent(),
            $this->getMultiFactorChallengeFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
        ]);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        $field = match (true) {
            blank($this->userUndertakingMultiFactorAuthentication) => 'data.email',
            (bool) data_get($this->data, 'multiFactor.app.useRecoveryCode') => 'data.multiFactor.app.recoveryCode',
            default => 'data.multiFactor.app.code',
        };

        $this->resetErrorBag($field);
        $this->addError($field, "Too many attempts. Wait {$exception->secondsUntilAvailable} seconds, then try again.");

        Log::warning('staff.sign_in_throttled', ['step' => $this->step(), 'staff_id' => $this->memberId()]);

        return null;
    }

    public function authenticate(): ?LoginResponse
    {
        $step = $this->step();

        try {
            return parent::authenticate();
        } catch (ValidationException $e) {
            // Never the email or password: only the step and, at step two, the member's id.
            Log::notice('staff.sign_in_failed', ['step' => $step, 'staff_id' => $step === 'second' ? $this->memberId() : null]);

            throw $e;
        }
    }

    private function step(): string
    {
        return blank($this->userUndertakingMultiFactorAuthentication) ? 'first' : 'second';
    }

    private function memberId(): ?string
    {
        $id = $this->getUserUndertakingMultiFactorAuthenticationData()['identifier'] ?? null;

        return is_string($id) ? $id : null;
    }
}
