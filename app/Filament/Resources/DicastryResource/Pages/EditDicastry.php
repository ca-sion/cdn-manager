<?php

namespace App\Filament\Resources\DicastryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\DicastryResource;

class EditDicastry extends EditRecord
{
    protected static string $resource = DicastryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
