<?php

namespace App\Filament\Resources\ProvisionElementResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Exports\ProvisionElementExporter;
use App\Filament\Resources\ProvisionElementResource;

class ListProvisionElements extends ListRecords
{
    protected static string $resource = ProvisionElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Exporter')
                ->exporter(ProvisionElementExporter::class)
                ->columnMapping(false),
        ];
    }
}
