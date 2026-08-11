<?php

namespace App\Filament\Resources\DicastryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\DicastryResource;

class ListDicastries extends ListRecords
{
    protected static string $resource = DicastryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
