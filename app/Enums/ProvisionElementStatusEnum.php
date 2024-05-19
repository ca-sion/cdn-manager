<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProvisionElementStatusEnum: string implements HasColor, HasLabel
{
    case ToPrepare = 'to_prepare';
    case ToContact = 'to_contact';
    case Contacted = 'contacted';
    case Sent = 'sent';
    case SentByPost = 'sent_by_post';
    case ToConfirm = 'to_confirm';
    case ToModify = 'to_modify';
    case ToRelaunch = 'to_relaunch';
    case Relaunched = 'relaunched';
    case Approved = 'approved';
    case ActionRequired = 'action_required';
    case Received = 'received';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case MadeBy = 'made_by';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ToPrepare      => 'À préparer',
            self::ToContact      => 'À contacter',
            self::Contacted      => 'Contacté',
            self::Sent           => 'Envoyé',
            self::SentByPost     => 'Envoyé par poste',
            self::ToConfirm      => 'À confirmer',
            self::ToModify       => 'À modifier',
            self::ToRelaunch     => 'À relancer',
            self::Relaunched     => 'Relancé',
            self::Approved       => 'Approuvé',
            self::ActionRequired => 'Action requise',
            self::Received       => 'Reçu',
            self::Suspended      => 'Suspendu',
            self::Cancelled      => 'Annulé',
            self::MadeBy         => 'Fait par…',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ToPrepare      => 'warning',
            self::ToContact      => 'warning',
            self::Contacted      => 'info',
            self::Sent           => 'success',
            self::SentByPost     => 'success',
            self::ToConfirm      => 'info',
            self::ToModify       => 'warning',
            self::ToRelaunch     => 'warning',
            self::Relaunched     => 'info',
            self::Approved       => 'success',
            self::ActionRequired => 'danger',
            self::Received       => 'success',
            self::Suspended      => 'gray',
            self::Cancelled      => 'gray',
            self::MadeBy         => 'gray',
        };
    }
}
