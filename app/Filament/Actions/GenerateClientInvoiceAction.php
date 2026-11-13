<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère une facture à partir des prestations de l'édition courante d'un client.
 */
class GenerateClientInvoiceAction extends Action
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Générer la facture')
            ->icon('heroicon-o-document-currency-dollar')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->action(function (?Model $record) {
                try {
                    $invoice = InvoiceService::generateInvoiceByClient($this->resolveClientId($record));
                    Notification::make()->title('Facture '.$invoice->number.' générée')->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
