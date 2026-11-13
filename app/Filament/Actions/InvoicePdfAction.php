<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class InvoicePdfAction extends Action
{
    use ResolvesInvoice;

    public static function getDefaultName(): ?string
    {
        return 'invoicePdf';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('PDF facture')
            ->icon('heroicon-o-document')
            ->openUrlInNewTab()
            ->visible(fn (?Model $record): bool => $record === null || $this->resolveInvoice($record) !== null)
            ->url(fn (?Model $record): ?string => $record ? $this->resolveInvoice($record)?->link : null);
    }
}
