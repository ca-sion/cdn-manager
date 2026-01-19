<?php

namespace App\Helpers;

class AppHelper
{
    public static function getCurrentEditionId(): ?string
    {
        return session()->get('edition_id') ?? setting('edition_id');
    }
}
