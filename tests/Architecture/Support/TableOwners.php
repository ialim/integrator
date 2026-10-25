<?php

namespace Tests\Architecture\Support;

use Illuminate\Support\Facades\Schema;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * Which module owns each table and view: the module whose
 * `Database/Migrations` or `Database/Views` creates it. Laravel's own
 * tables in `database/migrations` belong to the pseudo-module `framework`.
 */
final class TableOwners
{
    public const FRAMEWORK = 'framework';

    /**
     * @param  string  $modulesRoot  e.g. app/Modules
     * @param  list<string>  $frameworkMigrations  directories of framework migrations
     * @return array<string, string> table => module
     */
    public static function find(string $modulesRoot, array $frameworkMigrations = []): array
    {
        $owners = [];
        $modulesRoot = SourceFiles::normalise($modulesRoot);

        foreach (SourceFiles::in([$modulesRoot]) as $file) {
            if (! str_contains($file, '/Database/Migrations/') && ! str_contains($file, '/Database/Views/')) {
                continue;
            }

            $module = (string) SourceFiles::moduleOf($file, $modulesRoot);

            foreach (self::createdIn($file) as $table) {
                $owners[$table] ??= $module;
            }
        }

        foreach (SourceFiles::in($frameworkMigrations) as $file) {
            foreach (self::createdIn($file) as $table) {
                $owners[$table] ??= self::FRAMEWORK;
            }
        }

        return $owners;
    }

    /**
     * Tables and views a migration file creates.
     *
     * @return list<string>
     */
    public static function createdIn(string $file): array
    {
        $tables = [];
        $finder = new NodeFinder;
        $ast = SourceFiles::parse($file);

        /** @var list<Node\Expr\StaticCall> $calls */
        $calls = $finder->findInstanceOf($ast, Node\Expr\StaticCall::class);

        foreach ($calls as $call) {
            if (SourceFiles::className($call->class) === Schema::class
                && $call->name instanceof Node\Identifier
                && $call->name->toLowerString() === 'create'
                && ($call->args[0] ?? null) instanceof Node\Arg
                && $call->args[0]->value instanceof Node\Scalar\String_) {
                $tables[] = strtolower($call->args[0]->value->value);
            }
        }

        foreach (self::strings($ast) as $string) {
            if (preg_match_all('/\bcreate\s+(?:or\s+replace\s+)?(?:materialized\s+)?(?:table|view)\s+(?:if\s+not\s+exists\s+)?"?(\w+)"?/i', $string->value, $matches)) {
                array_push($tables, ...array_map('strtolower', $matches[1]));
            }
        }

        return array_values(array_unique($tables));
    }

    /**
     * Every string literal in an AST, including heredoc and nowdoc bodies.
     *
     * @param  list<Node\Stmt>  $ast
     * @return list<Node\Scalar\String_|Node\InterpolatedStringPart>
     */
    public static function strings(array $ast): array
    {
        /** @var list<Node\Scalar\String_|Node\InterpolatedStringPart> */
        return (new NodeFinder)->find($ast, fn (Node $node) => $node instanceof Node\Scalar\String_ || $node instanceof Node\InterpolatedStringPart);
    }
}
