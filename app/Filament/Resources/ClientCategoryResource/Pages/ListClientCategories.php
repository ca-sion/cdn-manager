<?php

namespace App\Filament\Resources\ClientCategoryResource\Pages;

use App\Filament\Resources\ClientCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientCategories extends ListRecords
{
    protected static string $resource = ClientCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
