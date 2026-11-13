<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoiceRelaunch;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class RelaunchInvoiceAction extends Action
{
    use ResolvesInvoice;

    public static function getDefaultName(): ?string
    {
        return 'relaunchInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer une relance')
            ->icon('heroicon-o-envelope')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $record === null || $this->resolveInvoice($record) !== null)
            ->action(function (Model $record) {
                $invoice = $this->resolveInvoice($record);
                $invoice?->client?->notify(new ClientSendInvoiceRelaunch($invoice));
                $invoice?->update(['status' => InvoiceStatusEnum::Relaunched]);
            });
    }
}
