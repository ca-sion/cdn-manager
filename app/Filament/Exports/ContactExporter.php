<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ContactExporter extends Exporter
{
    protected static ?string $model = Contact::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('first_name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('email'),
            ExportColumn::make('phone'),
            ExportColumn::make('company'),
            ExportColumn::make('role'),
            ExportColumn::make('department'),
            ExportColumn::make('address'),
            ExportColumn::make('address_extension'),
            ExportColumn::make('locality'),
            ExportColumn::make('postal_code'),
            ExportColumn::make('country_code'),
            ExportColumn::make('salutation'),
            ExportColumn::make('language'),
            ExportColumn::make('category.name'),
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
