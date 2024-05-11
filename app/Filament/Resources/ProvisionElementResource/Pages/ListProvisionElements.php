<?php

namespace App\Filament\Resources\ProvisionElementResource\Pages;

use App\Filament\Resources\ProvisionElementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvisionElements extends ListRecords
{
    protected static string $resource = ProvisionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
