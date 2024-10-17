<?php

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ContactCategoryResource;

class ListContactCategories extends ListRecords
{
    protected static string $resource = ContactCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
