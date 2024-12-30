<?php

namespace App\Filament\Exports;

use App\Models\Invoice;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class InvoiceExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('edition_id'),
            ExportColumn::make('client.name'),
            ExportColumn::make('status')->formatStateUsing(fn ($state): ?string => $state->value),
            ExportColumn::make('title'),
            ExportColumn::make('number'),
            ExportColumn::make('date'),
            ExportColumn::make('due_date'),
            ExportColumn::make('paid_on'),
            ExportColumn::make('viewed_at'),
            ExportColumn::make('reference'),
            ExportColumn::make('client_reference'),
            ExportColumn::make('is_pro_forma'),
            ExportColumn::make('include_vat'),
            ExportColumn::make('total_include_vat'),
            ExportColumn::make('total_exclude_vat'),
            ExportColumn::make('currency'),
            ExportColumn::make('positions')->listAsJson(),
            ExportColumn::make('payment_instructions'),
            ExportColumn::make('qr_reference'),
            ExportColumn::make('content'),
            ExportColumn::make('footer'),
            ExportColumn::make('order_column'),
            ExportColumn::make('note'),
            // ExportColumn::make('meta'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your invoice export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
