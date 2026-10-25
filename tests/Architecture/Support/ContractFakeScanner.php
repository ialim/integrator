<?php

namespace Tests\Architecture\Support;

use App\Modules\Shared\Providers\HasFake;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * AD-15: every provider interface in a module's `Contracts/` declares its
 * fake with #[HasFake(Fake::class)], and that fake implements the interface.
 */
final class ContractFakeScanner
{
    /**
     * @return list<Violation>
     */
    public function scan(string $modulesRoot): array
    {
        $violations = [];

        foreach (SourceFiles::in([$modulesRoot]) as $file) {
            if (! str_contains($file, '/Contracts/')) {
                continue;
            }

            /** @var list<Node\Stmt\Interface_> $interfaces */
            $interfaces = (new NodeFinder)->findInstanceOf(SourceFiles::parse($file), Node\Stmt\Interface_::class);

            foreach ($interfaces as $interface) {
                $name = (string) $interface->namespacedName?->toString();
                $problem = $this->problem($interface, $name);

                if ($problem !== null) {
                    $violations[] = new Violation(SourceFiles::relative($file), $interface->getStartLine(), $problem);
                }
            }
        }

        return $violations;
    }

    private function problem(Node\Stmt\Interface_ $interface, string $name): ?string
    {
        $fake = null;

        foreach ($interface->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if (SourceFiles::className($attribute->name) !== HasFake::class) {
                    continue;
                }

                $value = ($attribute->args[0] ?? null)?->value;

                if ($value instanceof Node\Expr\ClassConstFetch && $value->name instanceof Node\Identifier && $value->name->toLowerString() === 'class') {
                    $fake = SourceFiles::className($value->class);
                }
            }
        }

        if ($fake === null) {
            return "provider interface {$name} declares no fake; add #[HasFake(TheFake::class)]";
        }

        if (interface_exists($name)) {
            if (! class_exists($fake)) {
                return "provider interface {$name} declares fake {$fake}, which does not exist";
            }

            if (! is_subclass_of($fake, $name)) {
                return "fake {$fake} does not implement {$name}";
            }
        }

        return null;
    }
}
