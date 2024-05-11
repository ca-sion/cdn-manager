<?php

namespace App\Filament\Resources\ProvisionCategoryResource\Pages;

use App\Filament\Resources\ProvisionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvisionCategories extends ListRecords
{
    protected static string $resource = ProvisionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
