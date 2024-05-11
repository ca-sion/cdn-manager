<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ContactRoleEnum: string implements HasLabel
{
    case Invoicing = 'invoicing';
    case Executive = 'executive';
    case Commercial = 'commercial';
    case Administration = 'administration';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Invoicing => 'Facturation',
            self::Executive => 'Direction',
            self::Commercial => 'Commercial',
            self::Administration => 'Administration',
        };
    }
}
