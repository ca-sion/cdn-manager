<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Imports\ReconcileInvoiceImporter;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InvoiceResource;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(ReconcileInvoiceImporter::class)
                ->label('Rapprocher'),
        ];
    }
}
