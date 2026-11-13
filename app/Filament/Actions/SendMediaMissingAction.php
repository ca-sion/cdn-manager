<?php

namespace App\Filament\Actions;

use Closure;
use App\Models\Client;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Notifications\ClientAdvertiserMediaMissing;

/**
 * Demande au client ses visuels manquants (uniquement si une prestation de l'édition courante
 * exige un visuel et qu'il manque). Par défaut l'enregistrement est le client ;
 * ->clientUsing() permet de l'utiliser depuis une autre table.
 */
class SendMediaMissingAction extends Action
{
    protected ?Closure $clientResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'sendMediaMissing';
    }

    /**
     * Envoie la demande si un média manque. Retourne true si un email a été envoyé.
     */
    public static function sendTo(Client $client): bool
    {
        $elementsRequiringMedia = $client->currentProvisionElements
            ->filter(fn ($pe) => $pe->provision?->format_indicator !== null);

        if ($elementsRequiringMedia->isEmpty()) {
            return false;
        }

        if (! $elementsRequiringMedia->contains(fn ($pe) => $pe->getMedia('*')->isEmpty())) {
            return false;
        }

        $client->notify(new ClientAdvertiserMediaMissing);

        return true;
    }

    public function clientUsing(?Closure $callback): static
    {
        $this->clientResolver = $callback;

        return $this;
    }

    protected function resolveClient(?Model $record): ?Client
    {
        $client = ($record && $this->clientResolver) ? ($this->clientResolver)($record) : $record;

        return $client instanceof Client ? $client : null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Demander les médias manquants')
            ->icon('heroicon-o-envelope')
            ->requiresConfirmation()
            ->modalDescription('Un email n\'est envoyé que si un visuel exigé est manquant.')
            ->visible(fn (?Model $record): bool => $this->resolveClient($record) !== null)
            ->action(function (?Model $record) {
                $client = $this->resolveClient($record);

                if ($client && self::sendTo($client)) {
                    Notification::make()->title('Demande de média envoyée')->success()->send();
                } else {
                    Notification::make()->title('Aucun média manquant')->warning()->send();
                }
            });
    }
}
