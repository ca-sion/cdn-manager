<?php

namespace App\Filament\Resources\ProvisionCategoryResource\Pages;

use App\Filament\Resources\ProvisionCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvisionCategory extends EditRecord
{
    protected static string $resource = ProvisionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form')
        ];
    }
}
