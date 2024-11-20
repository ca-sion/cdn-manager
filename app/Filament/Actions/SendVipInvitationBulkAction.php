<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\BulkAction;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\RecipientSendVipInvitation;

class SendVipInvitationBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'sendVipInvitation';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer invitations VIP')
            ->icon('heroicon-m-envelope')
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                foreach ($records as $record) {
                    if ($record->provision_id == (int) setting('vip_provision')) {
                        if ($record->recipientVipContactEmail != null) {
                            $record->recipient->notify(new RecipientSendVipInvitation($record));
                            $record->status = ProvisionElementStatusEnum::Sent;
                            $record->save();
                        } else {
                            $record->status = ProvisionElementStatusEnum::ActionRequired;
                            $record->save();
                        }
                    }
                }
            });
    }
}
