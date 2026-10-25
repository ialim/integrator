<?php

namespace Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Finds PHP files and parses them into name-resolved ASTs.
 */
final class SourceFiles
{
    /** @var array<string, list<Node\Stmt>> */
    private static array $cache = [];

    private static ?Parser $parser = null;

    /**
     * @param  list<string>  $roots  directories (or single files) to search
     * @param  list<string>  $excludeFragments  skip any file whose normalised path contains one of these
     * @return list<string> absolute paths with forward slashes, sorted
     */
    public static function in(array $roots, array $excludeFragments = []): array
    {
        $files = [];

        foreach ($roots as $root) {
            $root = self::normalise($root);

            if (is_file($root)) {
                $files[] = $root;

                continue;
            }

            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = self::normalise($file->getPathname());
                }
            }
        }

        $files = array_filter($files, function (string $file) use ($excludeFragments): bool {
            foreach ($excludeFragments as $fragment) {
                if (str_contains($file, $fragment)) {
                    return false;
                }
            }

            return true;
        });

        $files = array_values(array_unique($files));
        sort($files);

        return $files;
    }

    /**
     * Parses a file with names resolved to fully qualified form and parent links set.
     *
     * @return list<Node\Stmt>
     */
    public static function parse(string $file): array
    {
        if (isset(self::$cache[$file])) {
            return self::$cache[$file];
        }

        self::$parser ??= (new ParserFactory)->createForHostVersion();

        $code = file_get_contents($file);

        if ($code === false) {
            throw new RuntimeException("Cannot read {$file}.");
        }

        $ast = self::$parser->parse($code) ?? [];

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new ParentConnectingVisitor);

        /** @var list<Node\Stmt> $ast */
        $ast = $traverser->traverse($ast);

        return self::$cache[$file] = $ast;
    }

    /**
     * The path as reported in a failure: relative to the project root.
     */
    public static function relative(string $file): string
    {
        $base = self::normalise(base_path()).'/';

        return str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }

    public static function normalise(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * The module a file belongs to, given the modules root, or null when it is outside.
     */
    public static function moduleOf(string $file, string $modulesRoot): ?string
    {
        $modulesRoot = self::normalise($modulesRoot).'/';

        if (! str_starts_with($file, $modulesRoot)) {
            return null;
        }

        return explode('/', substr($file, strlen($modulesRoot)))[0];
    }

    /**
     * The fully qualified class name a Name node refers to.
     */
    public static function className(Node $name): ?string
    {
        if (! $name instanceof Node\Name) {
            return null;
        }

        $resolved = $name->getAttribute('resolvedName');

        return ltrim(($resolved instanceof Node\Name ? $resolved : $name)->toString(), '\\');
    }
}
