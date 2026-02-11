<?php

namespace App\Filament\Actions;

use ZipArchive;
use App\Services\InvoiceService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Sprain\SwissQrBill\Exception\InvalidQrBillDataException;

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
                $errors = [];
                $successCount = 0;

                if ($zip->open($filePath, ZipArchive::CREATE) === true) {
                    foreach ($records as $invoice) {
                        try {
                            $pdf = InvoiceService::generatePdf($invoice);
                            $zip->addFromString($invoice->number.'.pdf', $pdf->output());
                            $successCount++;
                        } catch (InvalidQrBillDataException $e) {
                            $violations = [];
                            foreach ($e->getViolations() as $violation) {
                                $violations[] = $violation->getMessage();
                            }
                            $errors[] = "Facture {$invoice->number}: " . implode(', ', $violations);
                        } catch (\Exception $e) {
                            $errors[] = "Facture {$invoice->number}: " . $e->getMessage();
                        }
                    }
                    $zip->close();
                }

                if (count($errors) > 0) {
                    Notification::make()
                        ->title('Certaines factures n\'ont pas pu être générées')
                        ->danger()
                        ->body(implode('<br>', $errors))
                        ->persistent()
                        ->send();
                }

                if ($successCount === 0) {
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    return;
                }

                return response()->download($filePath)->deleteFileAfterSend(true);
            });
    }
}
