<?php

namespace App\Filament\Resources\RunRegistrationElementResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RunRegistrationElementResource;

class ListRunRegistrationElements extends ListRecords
{
    protected static string $resource = RunRegistrationElementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
