<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatusEnum: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sent = 'sent';
    case Relaunched = 'relaunched';
    case ActionRequired = 'action_required';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    // Old values
    case SentByPost = 'sent_by_post';
    case ToModify = 'to_modify';
    case ToRelaunch = 'to_relaunch';
    case Payed = 'payed';
    case MadeBy = 'made_by';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft          => '1. Brouillon',
            self::Ready          => '2. Prêt',
            self::Sent           => '3. Envoyé',
            self::Relaunched     => '3.. Relancé',
            self::ActionRequired => '3.. Action requise',
            self::Paid           => '4. Payé',
            self::Overdue        => '4.. En retard',
            self::Suspended      => 'Suspendu',
            self::Cancelled      => 'Annulé',

            self::SentByPost => 'X. Envoyé par poste',
            self::ToModify   => 'X. À modifier',
            self::ToRelaunch => 'X. À relancer',
            self::Payed      => 'X. Payé',
            self::MadeBy     => 'X. Fait par…',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft          => 'warning',
            self::Ready          => 'warning',
            self::Sent           => 'info',
            self::Relaunched     => 'info',
            self::ActionRequired => 'danger',
            self::Paid           => 'success',
            self::Overdue        => 'warning',
            self::Suspended      => 'gray',
            self::Cancelled      => 'gray',

            self::SentByPost => 'info',
            self::ToModify   => 'warning',
            self::ToRelaunch => 'warning',
            self::Payed      => 'success',
            self::MadeBy     => 'gray',
        };
    }
}
