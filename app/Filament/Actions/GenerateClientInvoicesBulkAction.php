<?php

namespace App\Filament\Actions;

use Exception;
use App\Services\InvoiceService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère une facture par client distinct de la sélection, avec ses prestations pas encore facturées.
 */
class GenerateClientInvoicesBulkAction extends BulkAction
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateInvoices';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Générer les factures')
            ->icon('heroicon-o-document-currency-dollar')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                $clientIds = $records->map(fn ($record) => $this->resolveClientId($record))->filter()->unique();
                $generated = 0;
                $nothing = 0;
                $legacy = 0;

                foreach ($clientIds as $clientId) {
                    if (InvoiceService::legacyProvisionInvoices($clientId)->isNotEmpty()) {
                        // Ancienne facture non traçable : on ne génère pas en masse, à faire client par client.
                        $legacy++;

                        continue;
                    }

                    try {
                        InvoiceService::generateInvoiceByClient($clientId);
                        $generated++;
                    } catch (Exception) {
                        $nothing++;
                    }
                }

                $details = array_filter([
                    $nothing ? "{$nothing} sans prestation à facturer" : null,
                    $legacy ? "{$legacy} ignoré(s) car ancienne facture à vérifier (à faire depuis la fiche client)" : null,
                ]);

                Notification::make()
                    ->title($generated.' facture(s) générée(s)')
                    ->body(implode(' · ', $details) ?: null)
                    ->{$details ? 'warning' : 'success'}()
                    ->send();
            });
    }
}
