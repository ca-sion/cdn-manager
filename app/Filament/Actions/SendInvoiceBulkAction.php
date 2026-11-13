<?php

namespace App\Filament\Actions;

use App\Enums\InvoiceStatusEnum;
use Filament\Actions\BulkAction;
use App\Notifications\ClientSendInvoice;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class SendInvoiceBulkAction extends BulkAction
{
    use ResolvesInvoice;

    public static function getDefaultName(): ?string
    {
        return 'sendInvoices';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer les factures')
            ->icon('heroicon-o-envelope')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                foreach ($records as $record) {
                    $invoice = $this->resolveInvoice($record);
                    if (! $invoice) {
                        continue;
                    }
                    $invoice->client?->notify(new ClientSendInvoice($invoice));
                    $invoice->update(['status' => InvoiceStatusEnum::Sent]);
                }
            });
    }
}
