<?php

namespace App\Filament\Imports;

use App\Models\Invoice;
use Illuminate\Support\Carbon;
use App\Enums\InvoiceStatusEnum;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

class ReconcileInvoiceImporter extends Importer
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('paid_on')
                ->label('Payé le')
                ->requiredMapping()
                ->guess(['Date de transaction'])
                ->castStateUsing(function (?string $state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    return Carbon::parse($state)->toDateString();
                }),
            ImportColumn::make('qr_reference')
                ->label('Référence QR')
                ->requiredMapping()
                ->guess(['Description 2']),
            ImportColumn::make('amount')
                ->label('Montant')
                ->requiredMapping()
                ->guess(['Crédit'])
                ->castStateUsing(function (?string $state): ?float {
                    if (blank($state)) {
                        return null;
                    }

                    $state = preg_replace('/[^0-9.]/', '', $state);
                    $state = floatval($state);

                    return $state;
                }),
            ImportColumn::make('transaction_number')
                ->label('Transaction')
                ->requiredMapping()
                ->guess(['N° de transaction']),
        ];
    }

    public function resolveRecord(): ?Invoice
    {
        // return Invoice::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        // return new Invoice();

        $invoice = Invoice::query()
            ->where('qr_reference', $this->data['qr_reference'])
            ->first();

        if (! $invoice) {
            throw new RowImportFailedException("Transaction #{$this->data['transaction_number']} non rapprochée.");
        }

        if ($invoice) {
            if ($invoice->total != $this->data['amount']) {
                throw new RowImportFailedException("Montant payé de la facture #{$invoice->number} inexact.");
            }
            $invoice->status = InvoiceStatusEnum::Paid->value;
            $invoice->paid_on = $this->data['paid_on'];
            $invoice->reference = empty($invoice->reference) ? $this->data['transaction_number'] : $invoice->reference;
            $invoice->save();
        }

        return null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Le rapprochement de factures a été fait sur '.number_format($import->successful_rows).' transactions.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' transactions n\'ont pas pu être traitées correctement.';
        }

        return $body;
    }
}
