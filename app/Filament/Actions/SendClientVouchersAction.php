<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\Client;
use App\Models\Voucher;
use App\Helpers\AppHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Notifications\ClientSendVouchers;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Envoie par email au client ses vouchers (et son quota Trail) pour l'édition courante.
 * Réutilisable depuis toute table liée à un client via ->clientIdUsing().
 */
class SendClientVouchersAction extends Action
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'sendClientVouchers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer les vouchers')
            ->icon('heroicon-o-ticket')
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->schema([
                Textarea::make('custom_message')
                    ->label('Message personnalisé (optionnel)'),
            ])
            ->action(function (?Model $record, array $data) {
                $client = Client::find($this->resolveClientId($record));
                if (! $client) {
                    Notification::make()->title('Client introuvable.')->danger()->send();

                    return;
                }

                $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
                $vouchers = Voucher::where('client_id', $client->id)->where('edition_id', $editionId)->get();

                if ($vouchers->isEmpty() && (int) $client->trail_vouchers_quota === 0) {
                    Notification::make()->title('Aucun voucher attribué et aucun quota Trail pour ce client.')->warning()->send();

                    return;
                }

                try {
                    $client->notify(new ClientSendVouchers($vouchers, $data['custom_message'] ?? null, $client->trail_vouchers_quota));
                    Notification::make()->title("Email de vouchers envoyé à {$client->name}")->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
