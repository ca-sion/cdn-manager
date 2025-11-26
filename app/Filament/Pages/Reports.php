<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Reports extends Page
{
    public static function getNavigationLabel(): string
    {
        return 'Rapports';
    }

    public function getTitle(): string
    {
        return 'Rapports';
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string $view = 'filament.pages.reports';
}
