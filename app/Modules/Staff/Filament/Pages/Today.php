<?php

namespace App\Modules\Staff\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * The first page of the console. It is empty until the role-aware queues of
 * Stories 1.4 and 1.5 arrive.
 */
final class Today extends Page
{
    protected static ?string $slug = 'today';

    protected static ?string $title = 'Today';

    protected static ?string $navigationLabel = 'Today';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = -100;

    protected string $view = 'staff::today';
}
