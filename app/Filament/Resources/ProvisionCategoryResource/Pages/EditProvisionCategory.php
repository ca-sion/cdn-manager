<?php

namespace App\Filament\Resources\ProvisionCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProvisionCategoryResource;

class EditProvisionCategory extends EditRecord
{
    protected static string $resource = ProvisionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
