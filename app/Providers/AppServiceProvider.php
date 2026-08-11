<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require_once app_path('Helpers/AppHelper.php');

        \Filament\Tables\Table::configureUsing(fn (\Filament\Tables\Table $table) => $table
            ->deferFilters(false));

        \Filament\Forms\Components\FileUpload::configureUsing(fn (\Filament\Forms\Components\FileUpload $fileUpload) => $fileUpload
            ->visibility('public'));

        \Filament\Tables\Columns\ImageColumn::configureUsing(fn (\Filament\Tables\Columns\ImageColumn $imageColumn) => $imageColumn
            ->visibility('public'));

        \Filament\Infolists\Components\ImageEntry::configureUsing(fn (\Filament\Infolists\Components\ImageEntry $imageEntry) => $imageEntry
            ->visibility('public'));
    }
}
