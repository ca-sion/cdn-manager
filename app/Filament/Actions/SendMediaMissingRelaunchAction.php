<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Notifications\ClientAdvertiserMediaMissingRelaunch;

/**
 * Envoie un rappel au client pour ses visuels manquants, en précisant une date limite
 * avant utilisation du visuel de l'année passée ou du logo d'entreprise.
 */
class SendMediaMissingRelaunchAction extends SendMediaMissingAction
{
    public static function getDefaultName(): ?string
    {
        return 'sendMediaMissingRelaunch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Relancer pour les médias manquants')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('Un e-mail de rappel n\'est envoyé que si un visuel exigé est manquant.')
            ->schema([
                TextInput::make('deadline')
                    ->label('Délai d\'envoi')
                    ->default(fn (): string => ClientAdvertiserMediaMissingRelaunch::defaultDeadline())
                    ->required(),
            ])
            ->action(function (?Model $record, array $data) {
                $client = $this->resolveClient($record);
                $deadline = $data['deadline'] ?? ClientAdvertiserMediaMissingRelaunch::defaultDeadline();

                if ($client && self::sendTo($client, new ClientAdvertiserMediaMissingRelaunch($deadline))) {
                    Notification::make()->title('Rappel pour médias manquants envoyé')->success()->send();
                } else {
                    Notification::make()->title('Aucun média manquant')->warning()->send();
                }
            });
    }
}
