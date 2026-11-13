<?php

namespace App\Filament\Actions;

use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Filament\Actions\Support\InvoiceMailing;
use App\Notifications\ClientSendInvoiceRelaunch;

class RelaunchInvoiceBulkAction extends InvoiceMailingBulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'relaunchInvoices';
    }

    protected function verb(): string
    {
        return 'Relance des factures';
    }

    protected function reason(?Invoice $invoice): ?string
    {
        return InvoiceMailing::relaunchReason($invoice);
    }

    protected function dispatchMail(Invoice $invoice): void
    {
        $invoice->client->notify(new ClientSendInvoiceRelaunch($invoice));
        $invoice->update(['status' => InvoiceStatusEnum::Relaunched]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer les relances')
            ->icon('heroicon-o-envelope')
            ->modalSubmitActionLabel('Envoyer les relances cochées');
    }
}
