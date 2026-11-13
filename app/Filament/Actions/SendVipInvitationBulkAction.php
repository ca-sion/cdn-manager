<?php

namespace App\Filament\Actions;

use App\Models\Client;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\RecipientSendVipInvitation;

/**
 * Envoie les invitations VIP.
 * - Par défaut, les enregistrements sont des prestations VIP (ProvisionElement).
 * - Avec ->forRecipients(), ce sont des destinataires (Client / Contact) : leur prestation VIP
 *   de l'édition courante est retrouvée automatiquement.
 */
class SendVipInvitationBulkAction extends BulkAction
{
    protected bool $forRecipients = false;

    public static function getDefaultName(): ?string
    {
        return 'sendVipInvitation';
    }

    public function forRecipients(bool $forRecipients = true): static
    {
        $this->forRecipients = $forRecipients;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer invitations VIP')
            ->icon('heroicon-m-envelope')
            ->color('warning')
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                $vipProvisionId = (int) setting('vip_provision');

                foreach ($records as $record) {
                    if ($this->forRecipients) {
                        $element = $record->currentProvisionElements()->where('provision_id', $vipProvisionId)->first();
                        $email = $record instanceof Client ? $record->vipContactEmail : $record->email;
                        $notifiable = $record;
                    } else {
                        $element = $record->provision_id == $vipProvisionId ? $record : null;
                        $email = $record->recipientVipContactEmail;
                        $notifiable = $record->recipient;
                    }

                    if (! $element) {
                        continue;
                    }

                    if ($email != null) {
                        $notifiable->notify(new RecipientSendVipInvitation($element));
                        $element->status = ProvisionElementStatusEnum::Sent;
                    } else {
                        $element->status = ProvisionElementStatusEnum::ActionRequired;
                    }
                    $element->save();
                }

                Notification::make()
                    ->title('Invitations VIP traitées ('.$records->count().')')
                    ->success()
                    ->send();
            });
    }
}
