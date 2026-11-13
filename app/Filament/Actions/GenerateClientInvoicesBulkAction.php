<?php

namespace App\Filament\Actions;

use Exception;
use App\Services\InvoiceService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère une facture par client distinct de la sélection (prestations de l'édition courante).
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
                $errors = 0;

                foreach ($clientIds as $clientId) {
                    try {
                        InvoiceService::generateInvoiceByClient($clientId);
                    } catch (Exception) {
                        $errors++;
                    }
                }

                Notification::make()
                    ->title(($clientIds->count() - $errors).' facture(s) générée(s)'.($errors ? " ({$errors} erreur(s))" : ''))
                    ->{$errors ? 'warning' : 'success'}()
                    ->send();
            });
    }
}
