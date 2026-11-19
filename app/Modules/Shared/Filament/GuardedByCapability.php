<?php

namespace App\Modules\Shared\Filament;

use App\Modules\Shared\Capabilities\NeedsCapability;
use Filament\Resources\Pages\Page as ResourcePage;
use Livewire\Attributes\Locked;
use LogicException;
use ReflectionClass;

/**
 * Makes a console page's or resource's #[NeedsCapability] declaration the
 * check (FR-52). Every page or resource that declares a capability uses this
 * trait, whichever module it is in; the architecture rule fails on one that
 * does not. The answer comes from {@see ConsolePageGuard}, which Staff binds
 * to the capability registry.
 *
 * - `canAccess()` asks the guard, so the rail leaves the link out and
 *   Filament refuses the page to anyone without it.
 * - A resource's pages need the resource's capability: they declare none of
 *   their own and use this trait for the refusal.
 * - On load, a member without the capability gets the page inside the console
 *   shell with only the words naming who can ("shown, and who can"), sent as
 *   403. Every later Livewire call on the page is refused with 403 in the same
 *   words.
 */
trait GuardedByCapability
{
    /** Why the signed-in member cannot use this page, naming who can; null when they can. */
    #[Locked]
    public ?string $refusal = null;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        return app(ConsolePageGuard::class)->allows(static::requiredCapability());
    }

    /** "The Staff page is for Admins. Ask Ada Founder." */
    public static function refusalWords(): string
    {
        $declarer = static::capabilityDeclarer();

        return app(ConsolePageGuard::class)->refusal((string) $declarer::getNavigationLabel(), static::requiredCapability());
    }

    /** The capability the declarer names with #[NeedsCapability]; a page without one is a programming error. */
    public static function requiredCapability(): string
    {
        $declarer = static::capabilityDeclarer();
        $attributes = (new ReflectionClass($declarer))->getAttributes(NeedsCapability::class);

        if ($attributes === []) {
            throw new LogicException("{$declarer} declares no capability with #[NeedsCapability].");
        }

        return $attributes[0]->newInstance()->capability;
    }

    /**
     * The class whose #[NeedsCapability] guards this one: the resource for a
     * resource's page, otherwise the class itself.
     *
     * @return class-string
     */
    public static function capabilityDeclarer(): string
    {
        if (is_subclass_of(static::class, ResourcePage::class)) {
            $resource = call_user_func([static::class, 'getResource']);

            if (is_string($resource) && class_exists($resource)) {
                return $resource;
            }
        }

        return static::class;
    }

    /** Livewire's mount hook for this trait: the in-shell refusal, sent as 403. */
    public function mountGuardedByCapability(): void
    {
        if (! static::canAccess()) {
            $this->refusal = static::refusalWords();
            app(ConsolePageGuard::class)->markRefused();
        }
    }

    /** Filament's own mount check would answer a bare 403; the refusal above replaces it. */
    public function mountCanAuthorizeAccess(): void {}

    /** Filament's own mount check for a resource's page; the refusal above replaces it. */
    public function mountCanAuthorizeResourceAccess(): void {}

    /** Every Livewire call after the load is refused outright, in words. */
    public function hydrateCanAuthorizeAccess(): void
    {
        abort_unless(static::canAccess(), 403, static::refusalWords());
    }

    /** Filament's resource-page check on every Livewire call, in words. */
    public static function authorizeResourceAccess(): void
    {
        abort_unless(static::canAccess(), 403, static::refusalWords());
    }

    /** A refused page shows only the words, in the console shell. */
    public function getView(): string
    {
        // Pages only: a resource never renders, so it never calls this.
        return $this->refusal === null ? parent::getView() : 'shared::refused-page'; // @phpstan-ignore staticMethod.notFound
    }

    /**
     * No header buttons on a refused page.
     *
     * @return array<mixed>
     */
    public function getCachedHeaderActions(): array
    {
        // Pages only, as above.
        return $this->refusal === null ? parent::getCachedHeaderActions() : []; // @phpstan-ignore staticMethod.notFound
    }
}
