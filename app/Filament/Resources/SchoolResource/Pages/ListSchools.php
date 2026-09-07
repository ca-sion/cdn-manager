<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\SchoolResource;

class ListSchools extends ListRecords
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
