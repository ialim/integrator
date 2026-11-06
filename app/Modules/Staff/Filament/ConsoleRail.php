<?php

namespace App\Modules\Staff\Filament;

use Filament\Navigation\NavigationGroup;

/**
 * The rail's groups, in order, with Today first (UX-DR29). A page or resource
 * names its group with one of these labels. Filament leaves out every link
 * whose `canAccess()` is false and every group left empty, so each role sees
 * only its own groups and links: nothing greyed out, no padlock.
 */
final class ConsoleRail
{
    public const QUEUES = 'Queues';

    public const WORK = 'Work';

    public const MONEY = 'Money';

    public const CATALOGUE = 'Catalogue';

    public const PEOPLE = 'People';

    public const RULES = 'Rules';

    public const WATCH = 'Watch';

    public const ORDER = [self::QUEUES, self::WORK, self::MONEY, self::CATALOGUE, self::PEOPLE, self::RULES, self::WATCH];

    /**
     * @return list<NavigationGroup>
     */
    public static function groups(): array
    {
        return array_map(
            fn (string $label): NavigationGroup => NavigationGroup::make($label)->collapsible(false),
            self::ORDER,
        );
    }
}
