<?php

namespace App\Filament\Actions;

use Closure;
use App\Models\Client;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class SendMediaMissingBulkAction extends BulkAction
{
    protected ?Closure $clientResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'sendMediaMissing';
    }

    public function clientUsing(?Closure $callback): static
    {
        $this->clientResolver = $callback;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Demander les médias manquants (avec contrôle)')
            ->icon('heroicon-o-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                $sent = $records
                    ->map(fn ($record) => $this->clientResolver ? ($this->clientResolver)($record) : $record)
                    ->filter(fn ($client) => $client instanceof Client)
                    ->unique('id')
                    ->filter(fn (Client $client) => SendMediaMissingAction::sendTo($client))
                    ->count();

                Notification::make()
                    ->title($sent.' email(s) pour médias manquants envoyé(s)')
                    ->success()
                    ->send();
            });
    }
}
