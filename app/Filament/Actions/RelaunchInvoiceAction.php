<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Actions\Support\InvoiceMailing;
use App\Notifications\ClientSendInvoiceRelaunch;
use App\Filament\Actions\Concerns\ResolvesInvoice;

/**
 * Envoie une relance. Visible seulement pour une facture déjà envoyée et impayée.
 */
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
            ->modalHeading('Envoyer une relance au client')
            ->modalDescription(function (?Model $record): ?string {
                $invoice = $record ? $this->resolveInvoice($record) : null;

                return $invoice ? 'Cet e-mail sera envoyé : '.InvoiceMailing::describe($invoice) : null;
            })
            ->visible(function (?Model $record): bool {
                if ($record === null) {
                    return true;
                }
                $invoice = $this->resolveInvoice($record);

                return $invoice !== null && $invoice->status?->canBeRelaunched() && $invoice->type() !== 'proforma';
            })
            ->action(function (Model $record) {
                $invoice = $this->resolveInvoice($record);

                if ($reason = InvoiceMailing::relaunchReason($invoice)) {
                    Notification::make()->title('Relance non envoyée')->body(ucfirst($reason))->warning()->send();

                    return;
                }

                $invoice->client->notify(new ClientSendInvoiceRelaunch($invoice));
                $invoice->update(['status' => InvoiceStatusEnum::Relaunched]);
                Notification::make()->title('Relance '.$invoice->number.' envoyée')->success()->send();
            });
    }
}
