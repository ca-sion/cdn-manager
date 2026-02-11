<?php

namespace App\Filament\Resources\RunRegistrationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\RunRegistrationResource;

class EditRunRegistration extends EditRecord
{
    protected static string $resource = RunRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
