<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EngagementStatusEnum: string implements HasColor, HasLabel
{
    case Idle = 'idle';
    case ActionRequired = 'action_required';
    case ToRelaunch = 'to_relaunch';
    case ToModify = 'to_modify';
    case Cancelled = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Idle           => 'En attente',
            self::ActionRequired => 'Action requise',
            self::ToRelaunch     => 'À relancer',
            self::ToModify       => 'À modifier',
            self::Cancelled      => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Idle           => 'warning',
            self::ActionRequired => 'danger',
            self::ToRelaunch     => 'info',
            self::ToModify       => 'warning',
            self::Cancelled      => 'gray',
        };
    }
}
