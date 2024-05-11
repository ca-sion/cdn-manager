<?php

namespace App\Filament\Resources\DicastryResource\Pages;

use App\Filament\Resources\DicastryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDicastry extends EditRecord
{
    protected static string $resource = DicastryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form')
        ];
    }
}
