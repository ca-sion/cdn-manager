<?php

namespace App\Filament\Resources\ProvisionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProvisionResource;

class EditProvision extends EditRecord
{
    protected static string $resource = ProvisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
