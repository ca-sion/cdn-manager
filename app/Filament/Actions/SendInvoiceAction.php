<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use Filament\Notifications\Notification;
use App\Filament\Actions\Support\InvoiceMailing;
use App\Filament\Actions\Concerns\ResolvesInvoice;

/**
 * Envoie la facture au client. Visible seulement pour une facture qui peut être envoyée
 * (Brouillon / Prêt / À modifier) : une facture payée ou déjà envoyée ne peut pas être « renvoyée »
 * par erreur (pour un rappel, utiliser la relance).
 */
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
            ->modalHeading('Envoyer la facture au client')
            ->modalDescription(function (?Model $record): ?string {
                $invoice = $record ? $this->resolveInvoice($record) : null;

                return $invoice ? 'Cet e-mail sera envoyé : '.InvoiceMailing::describe($invoice) : null;
            })
            ->visible(function (?Model $record): bool {
                if ($record === null) {
                    return true;
                }
                $invoice = $this->resolveInvoice($record);

                return $invoice !== null && $invoice->status?->canBeSent() && $invoice->type() !== 'proforma';
            })
            ->action(function (Model $record) {
                $invoice = $this->resolveInvoice($record);

                if ($reason = InvoiceMailing::sendReason($invoice)) {
                    Notification::make()->title('Facture non envoyée')->body(ucfirst($reason))->warning()->send();

                    return;
                }

                $invoice->client->notify(new ClientSendInvoice($invoice));
                $invoice->update(['status' => InvoiceStatusEnum::Sent]);
                Notification::make()->title('Facture '.$invoice->number.' envoyée')->success()->send();
            });
    }
}
