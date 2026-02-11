<?php

namespace App\Filament\Actions;

use ZipArchive;
use App\Services\InvoiceService;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ExportInvoicesPdfBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'exportInvoicesPdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Télécharger (.zip)')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function (Collection $records) {
                $zip = new ZipArchive;
                $fileName = 'factures-'.now()->format('Y-m-d-H-i-s').'.zip';
                $filePath = storage_path('app/'.$fileName);

                if ($zip->open($filePath, ZipArchive::CREATE) === true) {
                    foreach ($records as $invoice) {
                        $pdf = InvoiceService::generatePdf($invoice);
                        $zip->addFromString($invoice->number.'.pdf', $pdf->output());
                    }
                    $zip->close();
                }

                return response()->download($filePath)->deleteFileAfterSend(true);
            });
    }
}
