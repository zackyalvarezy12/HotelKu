<?php

namespace App\Filament\Owner\Pages;

use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan Hotel';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.owner.pages.reports';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('reports.hotel');
    }
}