<?php

namespace Tests\Architecture\Support;

use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeFinder;

/**
 * The foreign-key rule: a foreign key from one module's table to another
 * module's table must be ON DELETE RESTRICT. Foreign keys inside one module
 * are not constrained.
 */
final class ForeignKeyScanner
{
    /**
     * @param  list<string>  $frameworkMigrations
     * @return list<Violation>
     */
    public function scan(string $modulesRoot, array $frameworkMigrations = []): array
    {
        $modulesRoot = SourceFiles::normalise($modulesRoot);
        $owners = TableOwners::find($modulesRoot, $frameworkMigrations);
        $violations = [];

        $migrations = array_filter(
            SourceFiles::in([$modulesRoot]),
            fn (string $file) => str_contains($file, '/Database/Migrations/'),
        );

        foreach ([...$migrations, ...SourceFiles::in($frameworkMigrations)] as $file) {
            $module = SourceFiles::moduleOf($file, $modulesRoot) ?? TableOwners::FRAMEWORK;
            $ast = SourceFiles::parse($file);
            $relative = SourceFiles::relative($file);

            foreach ($this->blueprintForeignKeys($ast) as [$line, $table, $restricted]) {
                $owner = $table === null ? null : ($owners[$table] ?? null);

                if ($owner === $module || $restricted) {
                    continue;
                }

                $target = $table === null ? 'a table this rule cannot name (pass the table to constrained())' : $table.($owner === null ? ' (owner unknown)' : " (owned by {$owner})");
                $violations[] = new Violation($relative, $line, "foreign key from {$module} to {$target} must use ->restrictOnDelete()");
            }

            foreach (TableOwners::strings($ast) as $string) {
                if (! preg_match_all('/\breferences\s+"?(\w+)"?(?:\s*\([^)]*\))?([^,;]*)/i', $string->value, $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as [, $table, $tail]) {
                    $table = strtolower($table);
                    $owner = $owners[$table] ?? null;

                    if ($owner !== $module && preg_match('/\bon\s+delete\s+restrict\b/i', $tail) !== 1) {
                        $violations[] = new Violation($relative, $string->getStartLine(), "foreign key from {$module} to {$table} must be ON DELETE RESTRICT");
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Every Blueprint foreign-key chain in a migration.
     *
     * @param  list<Node\Stmt>  $ast
     * @return list<array{int, string|null, bool}> line, referenced table, whether it restricts deletes
     */
    private function blueprintForeignKeys(array $ast): array
    {
        $found = [];

        /** @var list<Node\Stmt\Expression> $statements */
        $statements = (new NodeFinder)->findInstanceOf($ast, Node\Stmt\Expression::class);

        foreach ($statements as $statement) {
            $calls = $this->chain($statement->expr);

            if ($calls === []) {
                continue;
            }

            $names = array_map(fn (Expr\MethodCall $call) => $call->name instanceof Node\Identifier ? $call->name->toLowerString() : '', $calls);

            if (! in_array('constrained', $names, true) && ! in_array('references', $names, true)) {
                continue;
            }

            $table = null;
            $restricted = false;

            foreach ($calls as $i => $call) {
                $name = $names[$i];
                $first = $this->stringArg($call, 0);

                if ($name === 'on' && $first !== null) {
                    $table = $first;
                }

                if ($name === 'constrained') {
                    $table = $first ?? $this->guessTable($calls[0]);
                }

                if ($name === 'restrictondelete' || ($name === 'ondelete' && strtolower((string) $first) === 'restrict')) {
                    $restricted = true;
                }
            }

            $found[] = [$statement->getStartLine(), $table === null ? null : strtolower($table), $restricted];
        }

        return $found;
    }

    /**
     * The method calls of a chain such as `$table->foreignUuid('x')->constrained()`, innermost first.
     *
     * @return list<Expr\MethodCall>
     */
    private function chain(Expr $expr): array
    {
        $calls = [];

        while ($expr instanceof Expr\MethodCall) {
            array_unshift($calls, $expr);
            $expr = $expr->var;
        }

        return $expr instanceof Expr\Variable ? $calls : [];
    }

    private function stringArg(Expr\MethodCall $call, int $position): ?string
    {
        $arg = $call->args[$position] ?? null;

        return $arg instanceof Node\Arg && $arg->value instanceof Node\Scalar\String_ ? $arg->value->value : null;
    }

    /**
     * Laravel's guess for `foreignId('order_id')->constrained()`: the plural of the column without its key suffix.
     */
    private function guessTable(Expr\MethodCall $first): ?string
    {
        $name = $first->name instanceof Node\Identifier ? $first->name->toLowerString() : '';
        $column = $this->stringArg($first, 0);

        if ($column === null || ! in_array($name, ['foreignid', 'foreignuuid', 'foreignulid'], true) || ! str_contains($column, '_')) {
            return null;
        }

        return Str::plural(Str::beforeLast($column, '_'));
    }
}
