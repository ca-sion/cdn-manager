<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MediaStatusEnum: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case ToRelaunch = 'to_relaunch';
    case Relaunched = 'relaunched';
    case ToModify = 'to_modify';
    case ActionRequired = 'action_required';
    case Received = 'received';
    case PhysicallyReceived = 'physically_received';
    case Missing = 'missing';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Requested          => 'Demandé',
            self::ToRelaunch         => 'À relancer',
            self::Relaunched         => 'Relancé',
            self::ToModify           => 'À modifier',
            self::ActionRequired     => 'Action requise',
            self::Received           => 'Reçu',
            self::PhysicallyReceived => 'Reçu physiquement',
            self::Missing            => 'Manquant',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Requested          => 'warning',
            self::ToRelaunch         => 'warning',
            self::Relaunched         => 'info',
            self::ToModify           => 'warning',
            self::ActionRequired     => 'danger',
            self::Received           => 'success',
            self::PhysicallyReceived => 'success',
            self::Missing            => 'danger',
        };
    }
}
