<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\ClientAdvertiserMediaMissingRelaunch;

class SendMediaMissingRelaunchBulkAction extends SendMediaMissingBulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'sendMediaMissingRelaunch';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Relancer pour les médias manquants (avec contrôle)')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->schema([
                TextInput::make('deadline')
                    ->label('Délai d\'envoi')
                    ->default(fn (): string => ClientAdvertiserMediaMissingRelaunch::defaultDeadline())
                    ->required(),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) {
                $deadline = $data['deadline'] ?? ClientAdvertiserMediaMissingRelaunch::defaultDeadline();
                $sent = $this->sendRecords($records, new ClientAdvertiserMediaMissingRelaunch($deadline));

                Notification::make()
                    ->title($sent.' rappel(s) pour médias manquants envoyé(s)')
                    ->success()
                    ->send();
            });
    }
}
