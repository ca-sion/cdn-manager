<?php

namespace App\Filament\Resources\ProvisionResource\Pages;

use App\Filament\Resources\ProvisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvisions extends ListRecords
{
    protected static string $resource = ProvisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
