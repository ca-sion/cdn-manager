<?php

namespace App\Filament\Resources\ClientCategoryResource\Pages;

use App\Filament\Resources\ClientCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientCategory extends EditRecord
{
    protected static string $resource = ClientCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form')
        ];
    }
}
