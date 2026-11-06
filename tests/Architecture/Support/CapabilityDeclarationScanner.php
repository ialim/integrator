<?php

namespace Tests\Architecture\Support;

use App\Modules\Shared\Actions\Action;
use App\Modules\Shared\Capabilities\NeedsCapability;
use App\Modules\Shared\Capabilities\NeedsNoCapability;
use App\Modules\Shared\Locks\LockAction;
use App\Modules\Staff\Filament\GuardedByCapability;
use Filament\Pages\BasePage;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Resources\Resource;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * FR-52: every concrete Action, every class in a module's `Actions/` folder
 * with a public `handle()` (Lock actions aside), and every console resource
 * or page declares the capability it needs with #[NeedsCapability('...')],
 * naming one the registry knows, or declares with #[NeedsNoCapability('why')]
 * that it has no staff actor to check. A declaration must also be the check:
 *
 * - an Action that needs a capability calls `$this->authorizeCapability()`,
 *   itself or in a base class;
 * - a console page or resource that needs one uses {@see GuardedByCapability};
 * - a resource's page declares nothing of its own (it needs its resource's
 *   capability) and uses {@see GuardedByCapability} for the refusal.
 */
final class CapabilityDeclarationScanner
{
    private const ACTION = 'Action';

    private const RESOURCE = 'console resource';

    private const RESOURCE_PAGE = 'resource page';

    private const PAGE = 'console page';

    /** @var array<string, string|null> class => parent, from the parsed files */
    private array $parents = [];

    /** @var array<string, bool> class => whether its own body calls $this->authorizeCapability() */
    private array $checks = [];

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $known  every capability name in the registry
     * @return list<Violation>
     */
    public function scan(array $roots, array $known): array
    {
        $classes = [];
        $finder = new NodeFinder;

        foreach (SourceFiles::in($roots) as $file) {
            /** @var list<Node\Stmt\Class_> $found */
            $found = $finder->findInstanceOf(SourceFiles::parse($file), Node\Stmt\Class_::class);

            foreach ($found as $class) {
                if ($class->namespacedName === null) {
                    continue; // anonymous classes
                }

                $name = $class->namespacedName->toString();
                $this->parents[$name] = $class->extends !== null ? SourceFiles::className($class->extends) : null;
                $this->checks[$name] = $this->callsAuthorizeCapability($class);
                $classes[] = [$file, $name, $class];
            }
        }

        $violations = [];

        foreach ($classes as [$file, $name, $class]) {
            if ($class->isAbstract()) {
                continue;
            }

            $kind = $this->kindOf($name, $file, $class);

            if ($kind === null) {
                continue;
            }

            $problem = $this->problem($class, $name, $kind, $known);

            if ($problem !== null) {
                $violations[] = new Violation(SourceFiles::relative($file), $class->getStartLine(), $problem);
            }
        }

        return $violations;
    }

    /**
     * @param  list<string>  $known
     */
    private function problem(Node\Stmt\Class_ $class, string $name, string $kind, array $known): ?string
    {
        $needs = [];
        $none = [];

        foreach ($class->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                $attributeName = SourceFiles::className($attribute->name);
                $value = ($attribute->args[0] ?? null)?->value;

                if ($attributeName === NeedsCapability::class) {
                    $needs[] = $value;
                } elseif ($attributeName === NeedsNoCapability::class) {
                    $none[] = $value;
                }
            }
        }

        if ($kind === self::RESOURCE_PAGE) {
            if ($needs !== [] || $none !== []) {
                return "{$kind} {$name} declares a capability of its own; a resource's pages need their resource's capability, so remove it";
            }

            return $this->usesGuard($class) ? null : "{$kind} {$name} does not use GuardedByCapability, so its resource's capability is not checked on load";
        }

        if ($needs === [] && $none === []) {
            return "{$kind} {$name} declares no capability; add #[NeedsCapability(...)] naming one from the capability registry, or #[NeedsNoCapability('why')] when it has no staff actor";
        }

        if (count($needs) + count($none) > 1) {
            return "{$kind} {$name} declares its capability more than once; keep exactly one #[NeedsCapability] or #[NeedsNoCapability]";
        }

        if ($none !== []) {
            $reason = $this->stringOf($none[0]);

            return $reason === null || trim($reason) === ''
                ? "{$kind} {$name} declares #[NeedsNoCapability] without a reason; say why it has no staff actor to check"
                : null;
        }

        $capability = $this->stringOf($needs[0]);

        if ($capability === null) {
            return "{$kind} {$name} names its capability in a way the test cannot read; use a string or a class constant";
        }

        if (! in_array($capability, $known, true)) {
            return "{$kind} {$name} needs capability {$capability}, which the capability registry does not know";
        }

        if ($kind === self::ACTION && ! $this->checksCapability($name)) {
            return "{$kind} {$name} declares capability {$capability} but never checks it; call \$this->authorizeCapability(\$actor) on the actor read under lock";
        }

        if (($kind === self::PAGE || $kind === self::RESOURCE) && ! $this->usesGuard($class)) {
            return "{$kind} {$name} declares capability {$capability} but does not use GuardedByCapability, so nothing checks it";
        }

        return null;
    }

    private function stringOf(?Node\Expr $value): ?string
    {
        if ($value instanceof Node\Scalar\String_) {
            return $value->value;
        }

        if ($value instanceof Node\Expr\ClassConstFetch && $value->name instanceof Node\Identifier) {
            $class = SourceFiles::className($value->class);
            $constant = $class.'::'.$value->name->toString();

            if ($class !== null && class_exists($class) && defined($constant)) {
                $resolved = constant($constant);

                return is_string($resolved) ? $resolved : null;
            }
        }

        return null;
    }

    private function kindOf(string $name, string $file, Node\Stmt\Class_ $class): ?string
    {
        if ($this->extends($name, Action::class)) {
            return self::ACTION;
        }

        if ($this->extends($name, Resource::class)) {
            return self::RESOURCE;
        }

        if ($this->extends($name, ResourcePage::class)) {
            return self::RESOURCE_PAGE;
        }

        if ($this->extends($name, BasePage::class)) {
            return self::PAGE;
        }

        // Kernel writers and other entry points in a module's Actions/ folder that are not Action subclasses.
        if (preg_match('#/Modules/[^/]+/Actions/#', $file) === 1 && ! $this->extends($name, LockAction::class) && $this->hasPublicHandle($class)) {
            return self::ACTION;
        }

        return null;
    }

    private function hasPublicHandle(Node\Stmt\Class_ $class): bool
    {
        $method = $class->getMethod('handle');

        return $method !== null && $method->isPublic();
    }

    private function usesGuard(Node\Stmt\Class_ $class): bool
    {
        foreach ($class->getTraitUses() as $use) {
            foreach ($use->traits as $trait) {
                if (SourceFiles::className($trait) === GuardedByCapability::class) {
                    return true;
                }
            }
        }

        return false;
    }

    private function callsAuthorizeCapability(Node\Stmt\Class_ $class): bool
    {
        /** @var list<Node\Expr\MethodCall> $calls */
        $calls = (new NodeFinder)->findInstanceOf($class->stmts, Node\Expr\MethodCall::class);

        foreach ($calls as $call) {
            if ($call->var instanceof Node\Expr\Variable && $call->var->name === 'this'
                && $call->name instanceof Node\Identifier && $call->name->toLowerString() === 'authorizecapability') {
                return true;
            }
        }

        return false;
    }

    /** Whether $class, or a base class among the parsed files, calls $this->authorizeCapability(). */
    private function checksCapability(string $class): bool
    {
        $seen = [];

        for ($current = $class; $current !== null && isset($this->checks[$current]) && ! isset($seen[$current]); $current = $this->parents[$current] ?? null) {
            $seen[$current] = true;

            if ($this->checks[$current]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether $class extends $base: through the parsed files' own `extends`
     * chains first (so fixtures need no autoloading), then by reflection.
     */
    private function extends(string $class, string $base): bool
    {
        $seen = [];

        for ($current = $this->parents[$class] ?? null; $current !== null && ! isset($seen[$current]); $current = $this->parents[$current] ?? null) {
            $seen[$current] = true;

            if ($current === $base) {
                return true;
            }

            if (! array_key_exists($current, $this->parents)) {
                return class_exists($current) && is_subclass_of($current, $base);
            }
        }

        return false;
    }
}
