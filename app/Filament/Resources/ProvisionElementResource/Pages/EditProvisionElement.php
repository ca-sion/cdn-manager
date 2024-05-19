<?php

namespace App\Filament\Resources\ProvisionElementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProvisionElementResource;

class EditProvisionElement extends EditRecord
{
    protected static string $resource = ProvisionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
