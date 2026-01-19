<?php

namespace App\Filament\Resources\RunRegistrationResource\Pages;

use App\Filament\Resources\RunRegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRunRegistrations extends ListRecords
{
    protected static string $resource = RunRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
