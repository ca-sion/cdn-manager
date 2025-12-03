<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions;
use App\Models\Invoice;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\InvoiceResource;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('gray')
                ->url(fn (Invoice $record): string => $record->link)
                ->openUrlInNewTab()
                ->icon('heroicon-o-document'),
            $this->getSaveFormAction()->formId('form'),
            Actions\DeleteAction::make(),
        ];
    }
}
