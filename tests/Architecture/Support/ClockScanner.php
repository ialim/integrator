<?php

namespace Tests\Architecture\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Facades\Date;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\NodeFinder;

/**
 * TC-2: code reads the current time only through the `Shared` Clock. Outside
 * `Shared`, flags now(), today(), time(), date() without a timestamp,
 * Carbon/Date ::now()-style calls, strtotime(), mktime(), microtime(), and
 * new DateTime*(), Carbon::parse/make/create or date_create*() with no
 * argument or with a relative literal such as 'now', '+2 hours' or 'tomorrow'.
 */
final class ClockScanner
{
    private const FUNCTIONS = [
        'now', 'today', 'time', 'date', 'date_create', 'date_create_immutable', 'gmdate', 'localtime', 'getdate',
        'strtotime', 'mktime', 'microtime',
    ];

    private const RELATIVE_WORDS = '/\b(?:now|today|tomorrow|yesterday|midnight|noon|next|last|ago)\b/i';

    private const STATIC_CLASSES = [Carbon::class, CarbonImmutable::class, IlluminateCarbon::class, Date::class];

    private const STATIC_METHODS = ['now', 'today', 'tomorrow', 'yesterday'];

    private const CONSTRUCTED = [DateTime::class, DateTimeImmutable::class, Carbon::class, CarbonImmutable::class, IlluminateCarbon::class];

    /**
     * @param  list<string>  $roots
     * @param  list<string>  $exempt  path fragments allowed to read the real time (the Shared kernel)
     * @return list<Violation>
     */
    public function scan(array $roots, array $exempt): array
    {
        $violations = [];
        $finder = new NodeFinder;

        foreach (SourceFiles::in($roots, $exempt) as $file) {
            $relative = SourceFiles::relative($file);

            foreach ($finder->find(SourceFiles::parse($file), fn (Node $node) => $node instanceof Expr\FuncCall || $node instanceof Expr\StaticCall || $node instanceof Expr\New_) as $node) {
                $message = match (true) {
                    $node instanceof Expr\FuncCall => $this->function($node),
                    $node instanceof Expr\StaticCall => $this->staticCall($node),
                    $node instanceof Expr\New_ => $this->construction($node),
                    default => null,
                };

                if ($message !== null) {
                    $violations[] = new Violation($relative, $node->getStartLine(), "reads the time with {$message}; use the Shared Clock");
                }
            }
        }

        return $violations;
    }

    private function function(Expr\FuncCall $call): ?string
    {
        if (! $call->name instanceof Node\Name || $call->name->isQualified()) {
            return null;
        }

        $name = $call->name->getLast();
        $lower = strtolower($name);

        if (! in_array($lower, self::FUNCTIONS, true)) {
            return null;
        }

        $arguments = count($call->args);

        $readsNow = match ($lower) {
            'date', 'gmdate' => $arguments < 2,
            'localtime', 'getdate' => $arguments === 0,
            'date_create', 'date_create_immutable' => $arguments === 0 || $this->isNowLiteral($call->args[0]),
            'strtotime' => $arguments < 2 || $this->isNowLiteral($call->args[0]),
            'mktime' => $arguments === 0,
            default => true,
        };

        return $readsNow ? "{$name}()" : null;
    }

    private function staticCall(Expr\StaticCall $call): ?string
    {
        $class = SourceFiles::className($call->class);

        if (! in_array($class, self::STATIC_CLASSES, true) || ! $call->name instanceof Node\Identifier) {
            return null;
        }

        if (in_array($call->name->toLowerString(), self::STATIC_METHODS, true)) {
            return class_basename($class)."::{$call->name}()";
        }

        if (in_array($call->name->toLowerString(), ['parse', 'make', 'create'], true)
            && ($call->args === [] || $this->isNowLiteral($call->args[0]))) {
            return class_basename($class)."::{$call->name}()";
        }

        return null;
    }

    private function construction(Expr\New_ $new): ?string
    {
        $class = SourceFiles::className($new->class);

        if (! in_array($class, self::CONSTRUCTED, true)) {
            return null;
        }

        return $new->args === [] || $this->isNowLiteral($new->args[0]) ? 'new '.class_basename($class).'()' : null;
    }

    /**
     * A literal read relative to the current time: empty, starting with + or -,
     * or naming now, today, tomorrow, yesterday, midnight, noon, next, last or ago.
     */
    private function isNowLiteral(Node $arg): bool
    {
        if (! $arg instanceof Node\Arg || ! $arg->value instanceof Node\Scalar\String_) {
            return false;
        }

        $value = trim($arg->value->value);

        return $value === ''
            || str_starts_with($value, '+')
            || str_starts_with($value, '-')
            || preg_match(self::RELATIVE_WORDS, $value) === 1;
    }
}
