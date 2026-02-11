<?php

namespace App\Filament\Resources\RunResource\Pages;

use Filament\Actions;
use App\Filament\Resources\RunResource;
use Filament\Resources\Pages\EditRecord;

class EditRun extends EditRecord
{
    protected static string $resource = RunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
