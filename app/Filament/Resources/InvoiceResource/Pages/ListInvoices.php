<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Filament\Pages\CamtImport;
use Filament\Actions\ExportAction;
use App\Filament\Exports\InvoiceExporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Imports\ReconcileInvoiceImporter;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(ReconcileInvoiceImporter::class)
                ->label('Rapprocher')
                ->tooltip('UBS: Fortune et placement > Comptes > Transactions > CSV'),
            ExportAction::make()
                ->label('Exporter')
                ->exporter(InvoiceExporter::class),
            Actions\Action::make('camtImport')
                ->label('Rapprocher CAMT 054')
                ->url(CamtImport::getUrl()),
        ];
    }
}
