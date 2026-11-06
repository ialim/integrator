<?php

namespace App\Modules\Staff\Capabilities;

use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Staff\Models\StaffMember;
use App\Modules\Staff\Models\StaffRole;
use App\Modules\Staff\StatusTables\StaffAccountStatus;
use LogicException;
use ReflectionClass;

/**
 * The FR-52 capability matrix, the single source of truth: each capability
 * and the groups that hold it (Finance, Operations, the Support duties, and
 * Admin, which holds every capability). Page access, the rail, every Action's
 * server check and the Staff page's Duties column all read it.
 *
 * A container singleton; the Staff module declares its own capabilities from
 * its service provider. Another module cannot reach this registry yet (it is
 * private to `Staff`, AD-2): the first module that adds a capability needs a
 * public way in first, such as a declaration list in `Shared` that the
 * registry reads. A person's groups are read from their account on every
 * request, so switching Support duties takes effect on their next request,
 * with no release.
 */
final class CapabilityRegistry
{
    public const NOT_ACTIVE = 'Your account is not active, so nothing was changed.';

    /** @var array<string, array{what: string, groups: list<CapabilityGroup>}> */
    private array $capabilities = [];

    /** @var array<string, string|null> */
    private array $declared = [];

    /**
     * Declares $capability, held by $groups and by Admin. $what names it in
     * words for a refusal: "The Support desk".
     */
    public function declare(string $capability, string $what, CapabilityGroup ...$groups): void
    {
        if (isset($this->capabilities[$capability])) {
            throw new LogicException("The capability {$capability} is declared twice.");
        }

        $holders = array_values(array_filter($groups, fn (CapabilityGroup $group): bool => $group !== CapabilityGroup::Admin));
        $this->capabilities[$capability] = ['what' => $what, 'groups' => [...$holders, CapabilityGroup::Admin]];
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->capabilities);
    }

    /**
     * The groups that hold $capability, Admin last.
     *
     * @return list<CapabilityGroup>
     */
    public function groupsHolding(string $capability): array
    {
        return $this->entry($capability)['groups'];
    }

    /**
     * The groups $member holds: their role's, and the Support duties for an
     * Operations account whose switch is On and that is not deactivated.
     *
     * @return list<CapabilityGroup>
     */
    public function groupsOf(StaffMember $member): array
    {
        $groups = [CapabilityGroup::ofRole($member->role)];

        if ($member->role === StaffRole::Operations && $member->support_duties && $member->status !== StaffAccountStatus::Deactivated) {
            $groups[] = CapabilityGroup::SupportDuties;
        }

        return $groups;
    }

    /**
     * Whether $member may use $capability now: an account that can sign in,
     * holding a group that holds it. Admin holds every capability. An unknown
     * capability is a programming error, never a quiet yes.
     */
    public function allows(StaffMember $member, string $capability): bool
    {
        $holding = $this->groupsHolding($capability);

        if (! $member->canSignIn()) {
            return false;
        }

        foreach ($this->groupsOf($member) as $group) {
            if (in_array($group, $holding, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The server's check before an Action changes anything: $actor must be
     * Active and hold $capability, whatever the screen offered. The words say
     * which is wrong: the actor's own account, or their role.
     *
     * @throws CapabilityRefused
     */
    public function authorize(StaffMember $actor, string $capability): void
    {
        if ($actor->status !== StaffAccountStatus::Active) {
            throw new CapabilityRefused($capability, self::NOT_ACTIVE);
        }

        if (! $this->allows($actor, $capability)) {
            throw new CapabilityRefused($capability, $this->actionRefusal($capability, $actor));
        }
    }

    /**
     * The capability $class declares with {@see NeedsCapability}, or null.
     *
     * @param  class-string  $class
     */
    public function declaredBy(string $class): ?string
    {
        if (! array_key_exists($class, $this->declared)) {
            $attributes = (new ReflectionClass($class))->getAttributes(NeedsCapability::class);
            $this->declared[$class] = $attributes === [] ? null : $attributes[0]->newInstance()->capability;
        }

        return $this->declared[$class];
    }

    /**
     * The capability $class declares, refusing a class that declares none.
     *
     * @param  class-string  $class
     */
    public function requiredBy(string $class): string
    {
        return $this->declaredBy($class) ?? throw new LogicException("{$class} declares no capability with #[NeedsCapability].");
    }

    /** "Admins", "Finance staff, Operations staff and Admins", "Operations staff with Support duties and Admins". */
    public function whoCan(string $capability): string
    {
        $words = array_map(fn (CapabilityGroup $group): string => $group->holders(), $this->groupsHolding($capability));
        $last = (string) array_pop($words);

        return $words === [] ? $last : implode(', ', $words).' and '.$last;
    }

    /**
     * Active people who hold $capability, alphabetically by name, leaving out
     * $except (the person being refused).
     *
     * @return list<StaffMember>
     */
    public function activeHolders(string $capability, ?StaffMember $except = null): array
    {
        $roles = [];
        $withSupportDuties = false;

        foreach ($this->groupsHolding($capability) as $group) {
            if ($group === CapabilityGroup::SupportDuties) {
                $withSupportDuties = true;

                continue;
            }

            $roles[] = match ($group) {
                CapabilityGroup::Finance => StaffRole::Finance->value,
                CapabilityGroup::Operations => StaffRole::Operations->value,
                CapabilityGroup::Admin => StaffRole::Admin->value,
            };
        }

        return StaffMember::query()
            ->where('status', StaffAccountStatus::Active->value)
            ->where(fn ($query) => $query
                ->whereIn('role', $roles)
                ->when($withSupportDuties, fn ($inner) => $inner->orWhere(fn ($ops) => $ops
                    ->where('role', StaffRole::Operations->value)
                    ->where('support_duties', true))))
            ->when($except !== null, fn ($query) => $query->whereKeyNot((string) $except?->id))
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->values()
            ->all();
    }

    /** "Ask Ada Founder.": the first active holder by name, or the role when nobody is active. */
    public function askWords(string $capability, ?StaffMember $except = null): string
    {
        $holder = $this->activeHolders($capability, $except)[0] ?? null;

        return $holder === null ? 'Ask an Admin.' : "Ask {$holder->name}.";
    }

    /** "The Staff page is for Admins. Ask Ada Founder." */
    public function pageRefusal(string $pageTitle, string $capability, ?StaffMember $except = null): string
    {
        return "The {$pageTitle} page is for {$this->whoCan($capability)}. {$this->askWords($capability, $except)}";
    }

    /** "Nothing was changed: managing staff accounts and their Support duties is for Admins. Ask Ada Founder." */
    public function actionRefusal(string $capability, ?StaffMember $except = null): string
    {
        return 'Nothing was changed: '.lcfirst($this->entry($capability)['what'])." is for {$this->whoCan($capability)}. {$this->askWords($capability, $except)}";
    }

    /** What $member looks after, in words: "Money", "Goods", "Goods and Support", "Everything". */
    public function duties(StaffMember $member): string
    {
        return $this->dutiesOf($this->groupsOf($member));
    }

    /** What a new account of $role looks after (Support duties start Off). */
    public function dutiesOfRole(StaffRole $role): string
    {
        return $this->dutiesOf([CapabilityGroup::ofRole($role)]);
    }

    /**
     * @param  list<CapabilityGroup>  $groups
     */
    private function dutiesOf(array $groups): string
    {
        if (in_array(CapabilityGroup::Admin, $groups, true)) {
            return CapabilityGroup::Admin->duty();
        }

        return implode(' and ', array_map(fn (CapabilityGroup $group): string => $group->duty(), $groups));
    }

    /**
     * @return array{what: string, groups: list<CapabilityGroup>}
     */
    private function entry(string $capability): array
    {
        return $this->capabilities[$capability] ?? throw new LogicException("The capability {$capability} is not in the registry.");
    }
}
