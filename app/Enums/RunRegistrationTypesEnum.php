<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RunRegistrationTypesEnum: string implements HasLabel
{
    case Company = 'company';
    case School = 'school';
    case Group = 'group';
    case Elite = 'elite';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Company => 'Entreprise',
            self::School => 'École',
            self::Group => 'Groupe',
            self::Elite => 'Élite',
        };
    }
}
