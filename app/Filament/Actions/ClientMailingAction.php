<?php

namespace App\Filament\Actions;

use Closure;
use Throwable;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Actions\Support\ClientMailing;

/**
 * Envoi / relance d'un des 4 emails clients (voir ClientMailing) pour UN client, avec la même fenêtre
 * explicative que l'action en masse : destinataire, dernier envoi, avertissements.
 * Un client déjà contacté demande une confirmation explicite ; un client non éligible ne peut pas être envoyé.
 *
 * Usage : ClientMailingAction::for('send_advertiser_form')
 * Depuis une autre page : ->clientUsing(fn ($record) => $record->recipient)
 */
class ClientMailingAction extends Action
{
    protected string $mailing = 'send_advertiser_form';

    protected ?Closure $clientResolver = null;

    public static function for(string $mailing): static
    {
        return static::make('mailing_'.$mailing)->mailing($mailing);
    }

    public function mailing(string $mailing): static
    {
        $definition = ClientMailing::definition($mailing);
        $this->mailing = $mailing;

        $this->label($definition['label'])
            ->icon($definition['icon'])
            ->modalHeading($definition['label'])
            ->modalSubmitActionLabel('Envoyer l\'email');

        return $this;
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

        $this->visible(fn (?Model $record): bool => $this->resolveClient($record) !== null)
            ->modalSubmitAction(function (?Model $record) {
                $client = $this->resolveClient($record);

                return $client && ClientMailing::assess($this->mailing, $client)['disabled'] ? false : null;
            })
            ->modalCancelActionLabel('Fermer')
            ->schema(function (?Model $record): array {
                $client = $this->resolveClient($record);

                if (! $client) {
                    return [];
                }

                $assessment = ClientMailing::assess($this->mailing, $client);
                $components = [Text::make($client->name)->weight('bold')];

                if ($assessment['disabled']) {
                    $components[] = Text::make('Impossible : '.$assessment['disabled'].'.')->color('danger');

                    return $components;
                }

                $components[] = Text::make('Destinataire : '.$assessment['email']);

                if ($assessment['info']) {
                    $components[] = Text::make(ucfirst($assessment['info']).'.')->color('gray');
                }

                if ($assessment['warning']) {
                    $components[] = Text::make('Attention : '.$assessment['warning'].'.')->color('warning');
                    $components[] = Toggle::make('confirm_again')->label('Je veux quand même lui renvoyer cet email')->required()->accepted();
                }

                $components[] = Text::make('Un email envoyé ne peut pas être annulé.')->color('gray');

                return $components;
            })
            ->action(function (?Model $record) {
                $client = $this->resolveClient($record);

                if (! $client) {
                    return;
                }

                $assessment = ClientMailing::assess($this->mailing, $client);

                if ($assessment['disabled']) {
                    Notification::make()->title('Email non envoyé')->body(ucfirst($assessment['disabled']))->warning()->send();

                    return;
                }

                try {
                    ClientMailing::send($this->mailing, $client);
                    Notification::make()->title('Email envoyé à '.$assessment['email'])->success()->send();
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
