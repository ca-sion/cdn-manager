<?php

namespace App\Filament\Actions;

use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Notifications\ClientSendInvoice;
use App\Filament\Actions\Support\InvoiceMailing;

class SendInvoiceBulkAction extends InvoiceMailingBulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'sendInvoices';
    }

    protected function verb(): string
    {
        return 'Envoi des factures';
    }

    protected function reason(?Invoice $invoice): ?string
    {
        return InvoiceMailing::sendReason($invoice);
    }

    protected function dispatchMail(Invoice $invoice): void
    {
        $invoice->client->notify(new ClientSendInvoice($invoice));
        $invoice->update(['status' => InvoiceStatusEnum::Sent]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer les factures')
            ->icon('heroicon-o-envelope')
            ->modalSubmitActionLabel('Envoyer les factures cochées');
    }
}
