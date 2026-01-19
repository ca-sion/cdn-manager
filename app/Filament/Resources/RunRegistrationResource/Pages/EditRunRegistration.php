<?php

namespace App\Filament\Resources\RunRegistrationResource\Pages;

use App\Filament\Resources\RunRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
