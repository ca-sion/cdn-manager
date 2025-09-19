<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProvisionElementStatusEnum: string implements HasColor, HasLabel
{
    case ToPrepare = 'to_prepare';
    case Confirmed = 'confirmed';
    case Ready = 'ready';
    case Done = 'done';
    case Cancelled = 'cancelled';

    // Old values
    case ToContact = 'to_contact';
    case Contacted = 'contacted';
    case Sent = 'sent';
    case SentByPost = 'sent_by_post';
    case Received = 'received';
    case ToConfirm = 'to_confirm';
    case ToModify = 'to_modify';
    case ToRelaunch = 'to_relaunch';
    case Relaunched = 'relaunched';
    case Approved = 'approved';
    case ActionRequired = 'action_required';
    case Suspended = 'suspended';
    case MadeBy = 'made_by';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ToPrepare => '1. À préparer',
            self::Confirmed => '2. Confirmé',
            self::Ready     => '3. Prêt',
            self::Done      => '4. Fait',
            self::Cancelled => 'Annulé',

            self::ToContact      => 'X. À contacter',
            self::Contacted      => 'X. Contacté',
            self::Sent           => 'X. Envoyé',
            self::SentByPost     => 'X. Envoyé par poste',
            self::Received       => 'Reçu',
            self::ToConfirm      => 'X. À confirmer',
            self::ToModify       => 'X. À modifier',
            self::ToRelaunch     => 'X. À relancer',
            self::Relaunched     => 'X. Relancé',
            self::Approved       => 'X. Approuvé',
            self::ActionRequired => 'Action requise',
            self::Suspended      => 'Suspendu',
            self::MadeBy         => 'X. Fait par…',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ToPrepare => 'warning',
            self::Confirmed => 'info',
            self::Ready     => 'success',
            self::Done      => 'success',
            self::Cancelled => 'gray',

            self::ToContact      => 'warning',
            self::Contacted      => 'info',
            self::Sent           => 'success',
            self::SentByPost     => 'success',
            self::Received       => 'success',
            self::ToConfirm      => 'info',
            self::ToModify       => 'warning',
            self::ToRelaunch     => 'warning',
            self::Relaunched     => 'info',
            self::Approved       => 'success',
            self::ActionRequired => 'danger',
            self::Suspended      => 'gray',
            self::MadeBy         => 'gray',
        };
    }
}
