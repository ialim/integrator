<?php

namespace Tests\Architecture\Support;

use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * AD-1: only a module's Actions write its tables. Flags, outside any
 * `Actions/` directory, write calls on Eloquent models (static calls on a
 * model class, method calls on a value known to be a model or a model
 * query) and query-builder writes through the DB facade.
 */
final class ModelWriteScanner
{
    public const WRITE_METHODS = [
        'save', 'savequietly', 'update', 'updatequietly', 'delete', 'deletequietly', 'create', 'createquietly',
        'forcecreate', 'forcecreatequietly', 'forcedelete', 'forcedeletequietly', 'insert', 'insertorignore',
        'insertgetid', 'insertusing', 'upsert', 'increment', 'incrementeach', 'decrement', 'decrementeach',
        'updateorcreate', 'firstorcreate', 'createorfirst', 'updateorinsert', 'destroy', 'restore', 'touch',
        'push', 'truncate', 'forcefill', 'saveorfail', 'updateorfail', 'deleteorfail', 'createmany',
        'createmanyquietly', 'savemany', 'savemanyquietly', 'forcecreatemany', 'attach', 'detach', 'sync',
        'syncwithoutdetaching', 'syncwithpivotvalues', 'toggle', 'updateexistingpivot',
    ];

    public const DB_WRITES = ['insert', 'update', 'delete', 'statement', 'unprepared', 'affectingstatement'];

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $exclude  path fragments to skip (schema migrations write through Schema and DB by design)
     * @return list<Violation>
     */
    public function scan(array $roots, array $exclude = []): array
    {
        $files = SourceFiles::in($roots, $exclude);
        $index = ClassIndex::of($files);
        $violations = [];

        foreach ($files as $file) {
            // Only a module's own top-level Actions/ directory is the write path; nested Actions/ folders (Filament) are not.
            if (preg_match('#/Modules/[^/]+/Actions/#', $file) === 1) {
                continue;
            }

            $visitor = new class($index, $file) extends NodeVisitorAbstract
            {
                /** @var list<Violation> */
                public array $violations = [];

                /** @var list<array<string, string>> variable name => class, per function scope */
                private array $scopes = [[]];

                /** @var list<array<string, string>> property name => class, per class */
                private array $properties = [];

                /** @var list<string|null> */
                private array $classes = [];

                public function __construct(private ClassIndex $index, private string $file) {}

                public function enterNode(Node $node): null
                {
                    if ($node instanceof Node\Stmt\ClassLike) {
                        $this->classes[] = $node->namespacedName?->toString();
                        $this->properties[] = $this->typedProperties($node);
                    }

                    if ($node instanceof Node\FunctionLike) {
                        $scope = $node instanceof Expr\ArrowFunction || $node instanceof Expr\Closure ? end($this->scopes) : [];

                        foreach ($node->getParams() as $param) {
                            if ($param->var instanceof Expr\Variable && is_string($param->var->name)) {
                                $type = $this->modelType($param->type);

                                if ($type !== null) {
                                    $scope[$param->var->name] = $type;
                                }
                            }
                        }

                        $this->scopes[] = $scope ?: [];
                    }

                    if ($node instanceof Expr\Assign && $node->var instanceof Expr\Variable && is_string($node->var->name)) {
                        $type = $this->modelOf($node->expr);
                        $last = array_key_last($this->scopes);

                        if ($type !== null) {
                            $this->scopes[$last][$node->var->name] = $type;
                        } else {
                            unset($this->scopes[$last][$node->var->name]);
                        }
                    }

                    if ($node instanceof Expr\StaticCall && $node->name instanceof Node\Identifier) {
                        $class = SourceFiles::className($node->class);
                        $method = $node->name->toLowerString();

                        if (in_array($method, ModelWriteScanner::WRITE_METHODS, true) && $this->index->isModel($class)) {
                            $this->flag($node, "writes a model outside an Action: {$class}::{$node->name}()");
                        }

                        if ($class === DB::class && in_array($method, ModelWriteScanner::DB_WRITES, true)) {
                            $this->flag($node, "writes through the query builder outside an Action: DB::{$node->name}()");
                        }
                    }

                    if (($node instanceof Expr\MethodCall || $node instanceof Expr\NullsafeMethodCall) && $node->name instanceof Node\Identifier) {
                        $method = $node->name->toLowerString();

                        if (in_array($method, ModelWriteScanner::WRITE_METHODS, true)) {
                            $model = $this->modelOf($node->var);

                            if ($model !== null) {
                                $this->flag($node, "writes a model outside an Action: {$model}->{$node->name}()");
                            } elseif ($this->isDbTableChain($node->var)) {
                                $this->flag($node, "writes through the query builder outside an Action: DB::table()->{$node->name}()");
                            }
                        }
                    }

                    return null;
                }

                public function leaveNode(Node $node): null
                {
                    if ($node instanceof Node\FunctionLike) {
                        array_pop($this->scopes);
                    }

                    if ($node instanceof Node\Stmt\ClassLike) {
                        array_pop($this->classes);
                        array_pop($this->properties);
                    }

                    return null;
                }

                /**
                 * The model class an expression evaluates to (or queries), if known.
                 */
                private function modelOf(Expr $expr): ?string
                {
                    return match (true) {
                        $expr instanceof Expr\Variable && $expr->name === 'this' => $this->index->isModel(end($this->classes) ?: null) ? (string) end($this->classes) : null,
                        $expr instanceof Expr\Variable && is_string($expr->name) => end($this->scopes)[$expr->name] ?? null,
                        $expr instanceof Expr\PropertyFetch && $expr->var instanceof Expr\Variable && $expr->var->name === 'this' && $expr->name instanceof Node\Identifier => (end($this->properties) ?: [])[$expr->name->toString()] ?? null,
                        $expr instanceof Expr\New_ => $this->index->isModel(SourceFiles::className($expr->class)) ? SourceFiles::className($expr->class) : null,
                        $expr instanceof Expr\StaticCall => $this->index->isModel(SourceFiles::className($expr->class)) ? SourceFiles::className($expr->class) : null,
                        $expr instanceof Expr\MethodCall, $expr instanceof Expr\NullsafeMethodCall => $this->modelOf($expr->var),
                        $expr instanceof Expr\Assign => $this->modelOf($expr->expr),
                        default => null,
                    };
                }

                private function isDbTableChain(Expr $expr): bool
                {
                    while ($expr instanceof Expr\MethodCall || $expr instanceof Expr\NullsafeMethodCall) {
                        $expr = $expr->var;
                    }

                    return $expr instanceof Expr\StaticCall
                        && SourceFiles::className($expr->class) === DB::class
                        && $expr->name instanceof Node\Identifier
                        && in_array($expr->name->toLowerString(), ['table', 'query', 'connection'], true);
                }

                private function modelType(?Node $type): ?string
                {
                    if ($type instanceof Node\NullableType) {
                        return $this->modelType($type->type);
                    }

                    if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
                        foreach ($type->types as $inner) {
                            if (($model = $this->modelType($inner)) !== null) {
                                return $model;
                            }
                        }

                        return null;
                    }

                    $class = $type instanceof Node\Name ? SourceFiles::className($type) : null;

                    return $this->index->isModel($class) ? $class : null;
                }

                /**
                 * @return array<string, string>
                 */
                private function typedProperties(Node\Stmt\ClassLike $class): array
                {
                    $properties = [];

                    foreach ($class->getProperties() as $property) {
                        $type = $this->modelType($property->type);

                        foreach ($property->props as $prop) {
                            if ($type !== null) {
                                $properties[$prop->name->toString()] = $type;
                            }
                        }
                    }

                    foreach ($class->getMethods() as $method) {
                        if ($method->name->toLowerString() !== '__construct') {
                            continue;
                        }

                        foreach ($method->params as $param) {
                            $type = $this->modelType($param->type);

                            if ($param->flags !== 0 && $type !== null && $param->var instanceof Expr\Variable && is_string($param->var->name)) {
                                $properties[$param->var->name] = $type;
                            }
                        }
                    }

                    return $properties;
                }

                private function flag(Node $node, string $message): void
                {
                    $this->violations[] = new Violation(SourceFiles::relative($this->file), $node->getStartLine(), $message);
                }
            };

            $traverser = new NodeTraverser;
            $traverser->addVisitor($visitor);
            $traverser->traverse(SourceFiles::parse($file));

            array_push($violations, ...$visitor->violations);
        }

        return $violations;
    }
}
