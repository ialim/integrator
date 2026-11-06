<?php

namespace App\Modules\Staff\Filament\Pages;

use App\Modules\Staff\Actions\AcceptInvitation;
use App\Modules\Staff\Actions\InvalidStaffDetails;
use App\Modules\Staff\Actions\InvitationUnusable;
use App\Modules\Staff\Actions\StaffPassword;
use App\Modules\Staff\Http\StaffSessionRecords;
use App\Modules\Staff\Models\StaffMember;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use SensitiveParameter;

/**
 * Where an invitation link opens: the invitee sets their password (the staff
 * password rule), is signed in, and is then sent straight to setting up an
 * authenticator app, so no other console page loads first. An expired, used
 * or replaced link says so in words and offers to ask an Admin.
 *
 * @property-read Schema $form
 */
final class AcceptInvitationPage extends SimplePage
{
    use WithRateLimiting;

    #[Locked]
    public string $token = '';

    #[Locked]
    public ?string $unusable = null;

    #[Locked]
    public string $memberName = '';

    #[Locked]
    public string $memberEmail = '';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(#[SensitiveParameter] string $token): void
    {
        $this->token = $token;

        try {
            $member = app(AcceptInvitation::class)->memberFor($token);
            $this->memberName = $member->name;
            $this->memberEmail = $member->email;
        } catch (InvitationUnusable $e) {
            $this->unusable = $e->getMessage();
        }

        $this->form->fill();
    }

    public function getTitle(): string
    {
        return $this->unusable === null ? 'Set up your staff console sign-in' : 'This invitation link does not work';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return $this->unusable === null
            ? "Welcome, {$this->memberName}. Choose a password for {$this->memberEmail}. Next you set up an authenticator app; no console page opens until both are done."
            : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('password')
                    ->label('New password')
                    ->password()
                    ->revealable(false)
                    ->autocomplete('new-password')
                    ->required()
                    ->helperText('At least '.StaffPassword::MIN_LENGTH.' characters, not one character repeated, and not containing your name or the start of your email address.')
                    ->validationMessages(['required' => 'Choose a password.']),
                TextInput::make('passwordConfirmation')
                    ->label('Type the password again')
                    ->password()
                    ->revealable(false)
                    ->autocomplete('new-password')
                    ->required()
                    ->same('password')
                    ->validationMessages([
                        'required' => 'Type the password again.',
                        'same' => 'The two passwords do not match.',
                    ])
                    ->dehydrated(false),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        if ($this->unusable !== null) {
            return $schema->components([
                Text::make(fn (): HtmlString => new HtmlString('<span role="status" class="console-notice">'.e((string) $this->unusable).'</span>')),
                Text::make(fn (): HtmlString => new HtmlString('<a class="console-link" href="'.e(Filament::getLoginUrl()).'">Go to the sign-in page</a>')),
            ]);
        }

        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('accept')
                ->footer([
                    Actions::make([
                        Action::make('accept')->label('Set the password and continue')->submit('accept'),
                    ])->fullWidth()->key('form-actions'),
                ]),
        ]);
    }

    public function accept(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $e) {
            throw ValidationException::withMessages(['data.password' => "Too many attempts. Wait {$e->secondsUntilAvailable} seconds, then try again."]);
        }

        $data = $this->form->getState();
        $password = (string) ($data['password'] ?? '');

        try {
            $member = app(AcceptInvitation::class)->handle($this->token, $password);
        } catch (InvalidStaffDetails $e) {
            throw ValidationException::withMessages(['data.'.$e->field => $e->getMessage()]);
        } catch (InvitationUnusable $e) {
            $this->unusable = $e->getMessage();

            return;
        }

        if (! $this->signIn($member)) {
            $this->unusable = InvitationUnusable::used()->getMessage();

            return;
        }

        $this->redirect(Filament::getUrl());
    }

    /**
     * The invitee is signed in on the staff guard, in a new session (the old
     * one destroyed) recorded at once under their lock; the console's
     * required second factor then sends every request to the authenticator
     * set-up. Returns false when an Admin's change landed first.
     */
    private function signIn(StaffMember $member): bool
    {
        $guard = Filament::auth();

        if ($guard->check()) {
            $guard->logout();
        }

        $guard->login($member);
        /** @var Session $session */
        $session = app('session.store');

        if (! app(StaffSessionRecords::class)->rotate($session, $member->id)) {
            $guard->logout();

            return false;
        }

        Log::info('staff.invitation_accepted', ['staff_id' => $member->id]);

        return true;
    }

    public function hasLogo(): bool
    {
        return true;
    }
}
