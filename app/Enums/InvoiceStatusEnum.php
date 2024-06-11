<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatusEnum: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sent = 'sent';
    case SentByPost = 'sent_by_post';
    case ToModify = 'to_modify';
    case ToRelaunch = 'to_relaunch';
    case Relaunched = 'relaunched';
    case ActionRequired = 'action_required';
    case Payed = 'payed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case MadeBy = 'made_by';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft          => 'Brouillon',
            self::Ready          => 'Prêt',
            self::Sent           => 'Envoyé',
            self::SentByPost     => 'Envoyé par poste',
            self::ToModify       => 'À modifier',
            self::ToRelaunch     => 'À relancer',
            self::Relaunched     => 'Relancé',
            self::ActionRequired => 'Action requise',
            self::Payed          => 'Payé',
            self::Suspended      => 'Suspendu',
            self::Cancelled      => 'Annulé',
            self::MadeBy         => 'Fait par…',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft          => 'warning',
            self::Ready          => 'warning',
            self::Sent           => 'success',
            self::SentByPost     => 'success',
            self::ToModify       => 'warning',
            self::ToRelaunch     => 'warning',
            self::Relaunched     => 'info',
            self::ActionRequired => 'danger',
            self::Payed          => 'success',
            self::Suspended      => 'gray',
            self::Cancelled      => 'gray',
            self::MadeBy         => 'gray',
        };
    }
}
