<?php

namespace App\Filament\Resources\DicastryResource\Pages;

use App\Filament\Resources\DicastryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDicastries extends ListRecords
{
    protected static string $resource = DicastryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
