<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Exports\ClientExporter;
use App\Filament\Imports\ClientImporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ClientResource;
use App\Filament\Pages\Concerns\CancelsBulkInvoices;

class ListClients extends ListRecords
{
    use CancelsBulkInvoices;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->label('Importer')
                ->importer(ClientImporter::class),
            ExportAction::make()
                ->label('Exporter')
                ->exporter(ClientExporter::class),
        ];
    }
}
