<?php

namespace App\Filament\Resources\ProvisionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProvisionResource;

class EditProvision extends EditRecord
{
    protected static string $resource = ProvisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
