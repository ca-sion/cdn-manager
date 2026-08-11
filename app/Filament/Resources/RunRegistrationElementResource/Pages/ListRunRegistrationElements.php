<?php

namespace App\Filament\Resources\RunRegistrationElementResource\Pages;

use App\Filament\Resources\RunRegistrationElementResource;
use Filament\Resources\Pages\ListRecords;

class ListRunRegistrationElements extends ListRecords
{
    protected static string $resource = RunRegistrationElementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
