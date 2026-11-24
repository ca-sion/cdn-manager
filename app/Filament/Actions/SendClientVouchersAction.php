<?php

namespace App\Filament\Actions;

use Exception;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Schemas\Components\Text;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Actions\Support\VoucherMailing;
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
            ->modalHeading(fn (?Model $record): string => 'Envoyer les vouchers — '.(Client::find($this->resolveClientId($record))?->name ?? 'Client'))
            ->modalSubmitActionLabel('Envoyer par email')
            ->schema(function (?Model $record): array {
                $client = Client::find($this->resolveClientId($record));
                if (! $client) {
                    return [Text::make('Client introuvable.')->color('danger')];
                }

                $assessment = VoucherMailing::assess($client);

                $components = [
                    Text::make($assessment['description'])
                        ->color($assessment['disabled'] ? 'danger' : ($assessment['warning'] ? 'warning' : 'success'))
                        ->weight('bold'),
                ];

                if (! $assessment['disabled']) {
                    $components[] = Textarea::make('custom_message')
                        ->label('Message personnalisé (optionnel)')
                        ->placeholder('Ex : Voici vos codes vouchers pour la Course de Noël...');
                }

                return $components;
            })
            ->action(function (?Model $record, array $data) {
                $client = Client::find($this->resolveClientId($record));
                if (! $client) {
                    Notification::make()->title('Client introuvable.')->danger()->send();

                    return;
                }

                $assessment = VoucherMailing::assess($client);
                if ($assessment['disabled']) {
                    Notification::make()
                        ->title('Envoi impossible : '.$assessment['disabled'])
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    VoucherMailing::send($client, $data['custom_message'] ?? null);
                    Notification::make()
                        ->title("Email de vouchers envoyé à {$client->name} ({$assessment['email']})")
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                }
            });
    }
}
