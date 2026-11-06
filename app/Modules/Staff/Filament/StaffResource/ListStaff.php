<?php

namespace App\Modules\Staff\Filament\StaffResource;

use App\Modules\Notifications\Queries\EmailDeliveries;
use App\Modules\Shared\Queries\OutboxEvents;
use App\Modules\Shared\StatusTables\TransitionRefused;
use App\Modules\Staff\Actions\CreateStaffAccount;
use App\Modules\Staff\Actions\DeactivateStaffAccount;
use App\Modules\Staff\Actions\InvalidStaffDetails;
use App\Modules\Staff\Actions\InvitationLinks;
use App\Modules\Staff\Actions\ResendInvitation;
use App\Modules\Staff\Actions\ResetSignIn;
use App\Modules\Staff\Events\StaffInvitationIssued;
use App\Modules\Staff\Filament\RefusalWords;
use App\Modules\Staff\Filament\StaffResource;
use App\Modules\Staff\Locks\LockStaffMember;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * The Staff page: a console table of every staff account (name and initial,
 * role, duties, status) with a fixed header and a caption, moved through with
 * J, K, Enter and /. Each change opens a dialog whose title is a question
 * naming the person and whose body lists what will happen; only the
 * irreversible button is red. A lost race or a refusal is shown in words above
 * the table, with who changed the account and when, never as an error page.
 */
final class ListStaff extends Page
{
    public const REFUSED = 'Only an Admin can open the Staff page. Ask an Admin to add, change or deactivate a staff account.';

    public const NO_LONGER_ADMIN = 'Nothing was changed: your account is no longer an active Admin account, so you cannot change staff accounts. Ask another Admin.';

    public const NOT_FOUND = 'That staff account was not found. Reload the Staff page and try again.';

    public const OWN_ACCOUNT = 'This is your own account; you will be signed out at once.';

    public const INVITATION_IN_DOUBT = 'The invitation email may not have arrived. Send a new invitation.';

    protected static string $resource = StaffResource::class;

    protected string $view = 'staff::staff-list';

    protected static ?string $title = 'Staff';

    public string $search = '';

    /** What the last change did, or why it was refused, in words. */
    #[Locked]
    public ?string $notice = null;

    #[Locked]
    public bool $noticeIsRefusal = false;

    /** The server refuses Finance and Operations, in the console's words. */
    public static function authorizeResourceAccess(): void
    {
        abort_unless(StaffResource::canAccess(), 403, self::REFUSED);
    }

    public function getTitle(): string
    {
        return 'Staff';
    }

    /**
     * @return Collection<int, StaffMember>
     */
    public function getMembers(): Collection
    {
        $search = trim($this->search);

        return StaffMember::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'ilike', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('email', 'ilike', '%'.addcslashes($search, '%_\\').'%')))
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'invited' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [$this->createAction()];
    }

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Add a staff member')
            ->modalHeading('Add a staff member')
            ->modalDescription($this->willHappen([
                'They get an Invited account, which cannot sign in until they finish setting it up.',
                'An email goes to the address below with a link that works for '.InvitationLinks::VALID_HOURS.' hours.',
                'With the link they choose a password, then set up an authenticator app on their phone; then the account is Active.',
            ]))
            ->modalWidth(Width::Large)
            ->schema([
                TextInput::make('name')->label('Full name')->required()->maxLength(255)->autocomplete('off'),
                TextInput::make('email')->label('Email address')->email()->required()->maxLength(255)->autocomplete('off'),
                Select::make('role')
                    ->label('Role')
                    ->options(collect(StaffRole::cases())->mapWithKeys(fn (StaffRole $role) => [$role->value => $role->label().': '.$role->duties()])->all())
                    ->required()
                    ->native(),
            ])
            ->modalSubmitActionLabel('Add and send the invitation')
            ->action(function (array $data): void {
                try {
                    $member = app(CreateStaffAccount::class)->handle($this->actingAdmin(), (string) ($data['name'] ?? ''), (string) ($data['email'] ?? ''), (string) ($data['role'] ?? ''));
                } catch (InvalidStaffDetails $e) {
                    throw ValidationException::withMessages(['mountedActions.0.data.'.$e->field => $e->getMessage()]);
                }

                $this->say("Added {$member->name} as {$member->role->label()}. An invitation is on its way to {$member->email}; the link works for ".InvitationLinks::VALID_HOURS.' hours.');
            });
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::Large)
            ->modalHeading(fn (array $arguments): string => 'Deactivate '.$this->nameOf($arguments).'?')
            ->modalDescription(fn (array $arguments): Htmlable => $this->willHappen([
                ...$this->ownAccountWarning($arguments),
                "{$this->nameOf($arguments)} is signed out of the console everywhere, at once.",
                'They can no longer sign in, and any invitation link they have stops working.',
                'This cannot be undone: a deactivated account cannot be turned back on. Add a new account if they come back.',
            ]))
            ->modalSubmitAction(fn (Action $action, array $arguments) => $action->color('danger')->label('Deactivate '.$this->nameOf($arguments)))
            ->modalCancelActionLabel('Keep the account')
            ->action(function (array $arguments): void {
                $this->attempt(function () use ($arguments): void {
                    $member = app(DeactivateStaffAccount::class)->handle($this->actingAdmin(), $this->idOf($arguments));
                    $this->say("Deactivated {$member->name}. They were signed out everywhere and can no longer sign in.");
                });
            });
    }

    public function resendAction(): Action
    {
        return Action::make('resend')
            ->label('Send a new invitation')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::Large)
            ->modalHeading(fn (array $arguments): string => 'Send '.$this->nameOf($arguments).' a new invitation?')
            ->modalDescription(fn (array $arguments): Htmlable => $this->willHappen([
                'A new link goes to '.$this->emailOf($arguments).'. It works for '.InvitationLinks::VALID_HOURS.' hours.',
                'The link sent before stops working at once.',
                'If they had already chosen a password, it is cleared and they are signed out: they start again with the new link.',
            ]))
            ->modalSubmitActionLabel('Send a new invitation')
            ->modalCancelActionLabel('Do not send')
            ->action(function (array $arguments): void {
                $this->attempt(function () use ($arguments): void {
                    $member = app(ResendInvitation::class)->handle($this->actingAdmin(), $this->idOf($arguments));
                    $this->say("Sent {$member->name} a new invitation. The earlier link no longer works.");
                });
            });
    }

    public function resetAction(): Action
    {
        return Action::make('reset')
            ->label('Reset sign-in')
            ->color('gray')
            ->modal()
            ->modalWidth(Width::Large)
            ->modalHeading(fn (array $arguments): string => 'Reset '.$this->nameOf($arguments).'\'s sign-in?')
            ->modalDescription(fn (array $arguments): Htmlable => $this->willHappen([
                ...$this->ownAccountWarning($arguments),
                'Their password and authenticator app are cleared, and they are signed out everywhere at once.',
                'A new invitation link goes to '.$this->emailOf($arguments).', valid for '.InvitationLinks::VALID_HOURS.' hours, to set both up again.',
                'This cannot be undone: the old password and authenticator app never work again.',
            ]))
            ->modalSubmitAction(fn (Action $action) => $action->color('danger')->label('Reset sign-in'))
            ->modalCancelActionLabel('Keep their sign-in')
            ->action(function (array $arguments): void {
                $this->attempt(function () use ($arguments): void {
                    $member = app(ResetSignIn::class)->handle($this->actingAdmin(), $this->idOf($arguments));
                    $this->say("Reset {$member->name}'s sign-in. A new invitation is on its way to {$member->email}.");
                });
            });
    }

    /**
     * Runs a change; a refusal (a lost race, the last-Admin rule) is shown in
     * words with the current state, never as an error page.
     */
    private function attempt(\Closure $change): void
    {
        try {
            $change();
        } catch (TransitionRefused $e) {
            $this->say(app(RefusalWords::class)->for($e), refusal: true);
        } catch (AuthorizationException) {
            // The acting Admin lost Admin rights meanwhile, say to another Admin deactivating them at the same moment.
            $this->say(self::NO_LONGER_ADMIN, refusal: true);
        } catch (ModelNotFoundException) {
            abort(404, self::NOT_FOUND);
        }
    }

    private function say(string $notice, bool $refusal = false): void
    {
        $this->notice = $notice;
        $this->noticeIsRefusal = $refusal;
    }

    private function actingAdmin(): StaffMember
    {
        $member = Filament::auth()->user();
        abort_unless($member instanceof StaffMember, 403, self::REFUSED);

        return $member;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function idOf(array $arguments): string
    {
        return self::memberIdFrom($arguments) ?? abort(404, self::NOT_FOUND);
    }

    /**
     * The member id from Livewire's arguments: a UUID, lower-cased; null for anything else.
     *
     * @param  array<string, mixed>  $arguments
     */
    private static function memberIdFrom(array $arguments): ?string
    {
        $id = $arguments['member'] ?? null;

        return is_string($id) && Str::isUuid($id) ? Str::lower($id) : null;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function memberOf(array $arguments): ?StaffMember
    {
        $id = self::memberIdFrom($arguments);

        return $id === null ? null : StaffMember::query()->find($id);
    }

    /**
     * The dialog's first line when the Admin is changing their own account.
     *
     * @param  array<string, mixed>  $arguments
     * @return list<string>
     */
    private function ownAccountWarning(array $arguments): array
    {
        return self::memberIdFrom($arguments) === Filament::auth()->id() ? [self::OWN_ACCOUNT] : [];
    }

    /**
     * Invited members whose latest invitation email may not have arrived: the
     * relay gave it up, or the provider's answer was unknown so it is never
     * sent again (owner's decision, 6 November 2026).
     *
     * @param  Collection<int, StaffMember>  $members
     * @return array<string, true>
     */
    public function invitationsInDoubt(Collection $members): array
    {
        $invited = $members
            ->filter(fn (StaffMember $member): bool => $member->status === StaffAccountStatus::Invited && $member->invitation_accepted_at === null)
            ->map(fn (StaffMember $member): string => $member->id)
            ->values()
            ->all();

        $latest = app(OutboxEvents::class)->latestOfTypes(LockStaffMember::ROOT, $invited, StaffInvitationIssued::types());
        $uncertain = array_flip(app(EmailDeliveries::class)->uncertain(array_values(array_map(fn (array $event): string => $event['id'], $latest))));
        $inDoubt = [];

        foreach ($latest as $memberId => $event) {
            if ($event['given_up'] || isset($uncertain[$event['id']])) {
                $inDoubt[$memberId] = true;
            }
        }

        return $inDoubt;
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function nameOf(array $arguments): string
    {
        return $this->memberOf($arguments)->name ?? 'this staff member';
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function emailOf(array $arguments): string
    {
        return $this->memberOf($arguments)->email ?? 'their email address';
    }

    /**
     * @param  list<string>  $lines
     */
    private function willHappen(array $lines): Htmlable
    {
        $items = implode('', array_map(fn (string $line) => '<li>'.e($line).'</li>', $lines));

        return new HtmlString('<p>What will happen:</p><ul class="console-dialog-list">'.$items.'</ul>');
    }
}
