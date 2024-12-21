<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Exports\ClientExporter;
use App\Filament\Imports\ClientImporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ClientResource;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
                ->label('Importer')
                ->importer(ClientImporter::class),
            ExportAction::make()
                ->label('Exporter')
                ->exporter(ClientExporter::class),
        ];
    }
}
