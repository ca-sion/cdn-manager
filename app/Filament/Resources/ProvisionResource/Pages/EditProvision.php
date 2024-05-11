<?php

namespace App\Filament\Resources\ProvisionResource\Pages;

use App\Filament\Resources\ProvisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
