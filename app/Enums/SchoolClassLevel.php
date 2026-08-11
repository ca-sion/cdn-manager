<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SchoolClassLevel: string implements HasLabel
{
    case H3 = '3H';
    case H4 = '4H';
    case H5 = '5H';
    case H6 = '6H';
    case H7 = '7H';
    case H8 = '8H';

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
