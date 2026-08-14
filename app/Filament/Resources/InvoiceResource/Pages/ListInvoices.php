<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use App\Filament\Pages\CamtImport;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
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
            CreateAction::make(),
            ImportAction::make()
                ->importer(ReconcileInvoiceImporter::class)
                ->label('Rapprocher')
                ->tooltip('UBS: Fortune et placement > Comptes > Transactions > CSV'),
            ExportAction::make()
                ->label('Exporter')
                ->exporter(InvoiceExporter::class),
            Action::make('camtImport')
                ->label('Rapprocher CAMT 054')
                ->url(CamtImport::getUrl()),
        ];
    }
}
