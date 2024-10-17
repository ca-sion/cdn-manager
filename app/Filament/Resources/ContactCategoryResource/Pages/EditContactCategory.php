<?php

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ContactCategoryResource;

class EditContactCategory extends EditRecord
{
    protected static string $resource = ContactCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
