<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EngagementStageEnum: string implements HasColor, HasLabel
{
    case Prospect = 'prospect';
    case ProposalSent = 'proposal_sent';
    case Confirmed = 'confirmed';
    case Billed = 'billed';
    case Paid = 'paid';
    case Lost = 'lost';
    case Suspended = 'suspended';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Prospect     => '1. À contacter',
            self::ProposalSent => '2. Envoyé',
            self::Confirmed    => '3. Confirmé',
            self::Billed       => '4. Facturé',
            self::Paid         => '5. Payé',
            self::Lost         => 'Perdu',
            self::Suspended    => 'Suspendu',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Prospect     => 'warning',
            self::ProposalSent => 'info',
            self::Confirmed    => 'info',
            self::Billed       => 'info',
            self::Paid         => 'success',
            self::Lost         => 'danger',
            self::Suspended    => 'gray',
        };
    }
}
