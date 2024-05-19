<?php

namespace App\Filament\Resources\ProvisionCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProvisionCategoryResource;

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
