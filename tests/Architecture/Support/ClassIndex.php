<?php

namespace Tests\Architecture\Support;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * Knows which class names are Eloquent models: from the parsed files' own
 * `extends` chains first (so fixtures need no autoloading), then by reflection.
 */
final class ClassIndex
{
    /** @var array<string, string|null> class => parent */
    private array $parents = [];

    /** @var array<string, array{file: string, line: int, traits: list<string>}> */
    private array $declarations = [];

    /** @var array<string, bool> */
    private array $isModel = [];

    /**
     * @param  list<string>  $files
     */
    public static function of(array $files): self
    {
        $index = new self;
        $finder = new NodeFinder;

        foreach ($files as $file) {
            /** @var list<Node\Stmt\Class_> $classes */
            $classes = $finder->findInstanceOf(SourceFiles::parse($file), Node\Stmt\Class_::class);

            foreach ($classes as $class) {
                if ($class->namespacedName === null) {
                    continue;
                }

                $name = $class->namespacedName->toString();
                $index->parents[$name] = $class->extends !== null ? SourceFiles::className($class->extends) : null;

                $traits = [];

                foreach ($class->getTraitUses() as $use) {
                    foreach ($use->traits as $trait) {
                        $traits[] = (string) SourceFiles::className($trait);
                    }
                }

                $index->declarations[$name] = ['file' => $file, 'line' => $class->getStartLine(), 'traits' => $traits];
            }
        }

        return $index;
    }

    public function isModel(?string $class): bool
    {
        if ($class === null || $class === '') {
            return false;
        }

        if (isset($this->isModel[$class])) {
            return $this->isModel[$class];
        }

        $this->isModel[$class] = false;

        if ($class === Model::class) {
            return $this->isModel[$class] = true;
        }

        if (array_key_exists($class, $this->parents)) {
            return $this->isModel[$class] = $this->isModel($this->parents[$class]);
        }

        return $this->isModel[$class] = class_exists($class) && is_subclass_of($class, Model::class);
    }

    /**
     * Model classes declared in the indexed files.
     *
     * @return array<string, array{file: string, line: int, traits: list<string>}>
     */
    public function declaredModels(): array
    {
        return array_filter($this->declarations, fn (string $class) => $this->isModel($class), ARRAY_FILTER_USE_KEY);
    }
}
