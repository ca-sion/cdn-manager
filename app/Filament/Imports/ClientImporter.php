<?php

namespace App\Filament\Imports;

use App\Models\Client;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class ClientImporter extends Importer
{
    protected static ?string $model = Client::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->rules(['required', 'max:255']),
            ImportColumn::make('long_name')
                ->rules(['max:255']),
            /* ImportColumn::make('type')
                ->rules(['max:255']), */
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['max:255']),
            ImportColumn::make('website')
                ->rules(['max:255']),
            ImportColumn::make('address')
                ->rules(['required', 'max:255']),
            ImportColumn::make('address_extension')
                ->rules(['max:255']),
            ImportColumn::make('locality')
                ->rules(['required', 'max:255']),
            ImportColumn::make('postal_code')
                ->rules(['required', 'max:255']),
            ImportColumn::make('country_code')
                ->rules(['max:255']),
            ImportColumn::make('iban')
                ->rules(['max:255']),
            ImportColumn::make('iban_qr')
                ->rules(['max:255']),
            ImportColumn::make('ide')
                ->rules(['max:255']),
            /* ImportColumn::make('logo')
                ->rules(['max:255']), */
            ImportColumn::make('invoicing_name')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('invoicing_address')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_address_extension')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_address_extension_two')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_postal_code')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_locality')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_country')
                ->rules(['max:255']),
            ImportColumn::make('invoicing_note')
                ->rules(['max:255']),
            ImportColumn::make('category')
                ->relationship(resolveUsing: 'name'),
            ImportColumn::make('note')
                ->rules(['max:255']),
        ];
    }

    public function resolveRecord(): ?Client
    {
        return Client::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
            'name' => $this->data['name'],
        ]);

        return new Client;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your client import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
