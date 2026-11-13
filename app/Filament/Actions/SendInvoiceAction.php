<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class SendInvoiceAction extends Action
{
    use ResolvesInvoice;

    public static function getDefaultName(): ?string
    {
        return 'sendInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer la facture')
            ->icon('heroicon-o-envelope')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $record === null || $this->resolveInvoice($record) !== null)
            ->action(function (Model $record) {
                $invoice = $this->resolveInvoice($record);
                $invoice?->client?->notify(new ClientSendInvoice($invoice));
                $invoice?->update(['status' => InvoiceStatusEnum::Sent]);
            });
    }
}
