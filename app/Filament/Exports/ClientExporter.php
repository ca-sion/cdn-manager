<?php

namespace App\Filament\Exports;

use App\Models\Client;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ClientExporter extends Exporter
{
    protected static ?string $model = Client::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('name'),
            ExportColumn::make('long_name'),
            ExportColumn::make('type'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('website'),
            ExportColumn::make('address'),
            ExportColumn::make('address_extension'),
            ExportColumn::make('locality'),
            ExportColumn::make('postal_code'),
            ExportColumn::make('country_code'),
            ExportColumn::make('iban'),
            ExportColumn::make('iban_qr'),
            ExportColumn::make('ide'),
            ExportColumn::make('logo'),
            ExportColumn::make('invoicing_name'),
            ExportColumn::make('invoicing_email'),
            ExportColumn::make('invoicing_address'),
            ExportColumn::make('invoicing_address_extension'),
            ExportColumn::make('invoicing_address_extension_two'),
            ExportColumn::make('invoicing_postal_code'),
            ExportColumn::make('invoicing_locality'),
            ExportColumn::make('invoicing_country'),
            ExportColumn::make('invoicing_note'),
            ExportColumn::make('category.name'),
            ExportColumn::make('order_column'),
            ExportColumn::make('note'),
            ExportColumn::make('meta'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your client export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
