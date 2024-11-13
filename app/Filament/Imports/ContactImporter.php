<?php

namespace App\Filament\Imports;

use App\Models\Contact;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            /*
            ImportColumn::make('name')
                ->rules(['max:255']),
            */
            ImportColumn::make('first_name')
                ->rules(['max:255']),
            ImportColumn::make('last_name')
                ->rules(['max:255']),
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['max:255']),
            ImportColumn::make('company')
                ->rules(['max:255']),
            ImportColumn::make('role')
                ->rules(['max:255']),
            ImportColumn::make('department')
                ->rules(['max:255']),
            ImportColumn::make('address')
                ->rules(['max:255']),
            ImportColumn::make('locality')
                ->rules(['max:255']),
            ImportColumn::make('postal_code')
                ->rules(['max:255']),
            ImportColumn::make('country_code')
                ->rules(['max:255']),
            ImportColumn::make('salutation')
                ->rules(['max:255']),
            ImportColumn::make('language')
                ->rules(['max:255']),
            ImportColumn::make('category')
                ->relationship(),
            // ImportColumn::make('meta'),
        ];
    }

    public function resolveRecord(): ?Contact
    {
        return Contact::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
            'first_name' => $this->data['first_name'],
            'last_name'  => $this->data['last_name'],
        ]);

        return new Contact();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
