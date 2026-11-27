<?php

namespace App\Filament\Actions;

use Closure;
use App\Models\Client;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification as LaravelNotification;

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

    protected function sendRecords(Collection $records, ?LaravelNotification $notification = null): int
    {
        return $records
            ->map(fn ($record) => $this->clientResolver ? ($this->clientResolver)($record) : $record)
            ->filter(fn ($client) => $client instanceof Client)
            ->unique('id')
            ->filter(fn (Client $client) => SendMediaMissingAction::sendTo($client, $notification))
            ->count();
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
                $sent = $this->sendRecords($records);

                Notification::make()
                    ->title($sent.' email(s) pour médias manquants envoyé(s)')
                    ->success()
                    ->send();
            });
    }
}
