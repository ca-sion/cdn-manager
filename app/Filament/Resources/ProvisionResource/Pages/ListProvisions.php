<?php

namespace App\Filament\Resources\ProvisionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ProvisionResource;

class ListProvisions extends ListRecords
{
    protected static string $resource = ProvisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
