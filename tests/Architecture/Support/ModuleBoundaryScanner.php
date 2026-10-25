<?php

namespace Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * AD-2: a module reaches another module only through that module's public
 * Actions, Queries and Lock actions. It never imports another module's
 * classes outside those, and never names another module's table.
 * `Shared` is the kernel: everything in it is public except its Models.
 */
final class ModuleBoundaryScanner
{
    public const PUBLIC_PARTS = ['Actions', 'Queries', 'Locks'];

    private const TABLE_METHODS = [
        'table', 'from', 'fromsub', 'fromraw', 'join', 'leftjoin', 'rightjoin', 'crossjoin', 'joinwhere',
        'leftjoinwhere', 'rightjoinwhere', 'joinsub', 'leftjoinsub', 'rightjoinsub', 'setTable',
    ];

    private const SQL_TABLE = '/\b(?:from|join|into|update|table|references)\s+(?:only\s+)?(?:"?public"?\.)?"?([a-z_][a-z0-9_]*)"?/i';

    /**
     * @param  string  $modulesRoot  directory holding one folder per module
     * @param  string  $namespace  namespace of that directory, e.g. App\Modules
     * @param  list<string>  $frameworkMigrations
     * @return list<Violation>
     */
    public function scan(string $modulesRoot, string $namespace, array $frameworkMigrations = []): array
    {
        $modulesRoot = SourceFiles::normalise($modulesRoot);
        $owners = TableOwners::find($modulesRoot, $frameworkMigrations);
        $violations = [];

        foreach (SourceFiles::in([$modulesRoot]) as $file) {
            $module = (string) SourceFiles::moduleOf($file, $modulesRoot);
            $ast = SourceFiles::parse($file);
            $relative = SourceFiles::relative($file);

            foreach ($this->foreignNames($ast, $namespace, $module) as [$line, $name, $other]) {
                $violations[] = new Violation($relative, $line, "{$module} uses {$name}, which is private to {$other}; use {$other}'s Actions, Queries or Lock actions");
            }

            if (str_contains($file, '/Database/Migrations/')) {
                continue; // migrations may reference other modules' tables in foreign keys (checked by the foreign-key rule)
            }

            foreach ($this->tableReferences($ast) as [$line, $table]) {
                $owner = $owners[$table] ?? null;

                if ($owner !== null && $owner !== $module && $owner !== TableOwners::FRAMEWORK) {
                    $violations[] = new Violation($relative, $line, "{$module} queries table {$table}, which belongs to {$owner}; use {$owner}'s Queries");
                }
            }
        }

        return $violations;
    }

    /**
     * @param  list<Node\Stmt>  $ast
     * @return list<array{int, string, string}>
     */
    private function foreignNames(array $ast, string $namespace, string $module): array
    {
        $prefix = trim($namespace, '\\').'\\';
        $found = [];

        /** @var list<Node\Name> $names */
        $names = (new NodeFinder)->findInstanceOf($ast, Node\Name::class);

        foreach ($names as $name) {
            if ($name->getAttribute('parent') instanceof Node\Stmt\Namespace_) {
                continue;
            }

            $full = (string) SourceFiles::className($name);

            if (! str_starts_with($full, $prefix)) {
                continue;
            }

            $parts = explode('\\', substr($full, strlen($prefix)));
            $other = $parts[0];
            $area = $parts[1] ?? '';

            if ($other === $module || count($parts) < 2) {
                continue;
            }

            $allowed = $other === 'Shared' ? $area !== 'Models' : in_array($area, self::PUBLIC_PARTS, true);

            if (! $allowed) {
                $found[$name->getStartLine().$full] = [$name->getStartLine(), $full, $other];
            }
        }

        return array_values($found);
    }

    /**
     * Table names a file names: arguments of query-builder table methods,
     * an Eloquent `$table` property, `exists:`/`unique:` validation rules,
     * and tables named in SQL text.
     *
     * @param  list<Node\Stmt>  $ast
     * @return list<array{int, string}>
     */
    private function tableReferences(array $ast): array
    {
        $found = [];
        $methods = array_map('strtolower', self::TABLE_METHODS);

        foreach (TableOwners::strings($ast) as $string) {
            $value = $string->value;
            $line = $string->getStartLine();
            $parent = $string->getAttribute('parent');
            $call = $parent instanceof Node\Arg ? $parent->getAttribute('parent') : null;

            if (($call instanceof Node\Expr\MethodCall || $call instanceof Node\Expr\StaticCall || $call instanceof Node\Expr\NullsafeMethodCall)
                && $call->name instanceof Node\Identifier
                && in_array($call->name->toLowerString(), $methods, true)) {
                $found[] = [$line, strtolower(preg_split('/\s+as\s+/i', trim($value))[0] ?? $value)];
            }

            if ($parent instanceof Node\PropertyItem && $parent->name->toString() === 'table') {
                $found[] = [$line, strtolower($value)];
            }

            if (preg_match_all('/\b(?:exists|unique):([a-z_][a-z0-9_]*)/i', $value, $matches)) {
                foreach ($matches[1] as $table) {
                    $found[] = [$line, strtolower($table)];
                }
            }

            if (preg_match('/^\s*(?:select|insert|update|delete|with|create|alter)\b/i', $value) === 1
                && preg_match_all(self::SQL_TABLE, $value, $matches)) {
                foreach ($matches[1] as $table) {
                    $found[] = [$line, strtolower($table)];
                }
            }
        }

        return $found;
    }
}
