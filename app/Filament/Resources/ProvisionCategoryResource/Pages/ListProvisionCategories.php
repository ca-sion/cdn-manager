<?php

namespace App\Filament\Resources\ProvisionCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProvisionCategoryResource;

class ListProvisionCategories extends ListRecords
{
    protected static string $resource = ProvisionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
