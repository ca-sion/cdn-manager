<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use App\Services\RunRegistrationService;
use Filament\Notifications\Notification;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère / met à jour la facture consolidée des inscriptions de course d'un client.
 */
class GenerateRegistrationInvoiceAction extends Action
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateRegistrationInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Facture des inscriptions')
            ->icon('heroicon-o-document-currency-dollar')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->action(function (?Model $record) {
                try {
                    app(RunRegistrationService::class)->createInvoiceForClient($this->resolveClientId($record));
                    Notification::make()->title('Facture générée / mise à jour avec succès !')->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
