<?php

namespace App\Filament\Resources\ContactResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ContactResource;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}
