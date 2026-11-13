<?php

namespace App\Filament\Resources\RunRegistrationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\RunRegistrationResource;
use App\Filament\Pages\Concerns\CancelsBulkInvoices;

class ListRunRegistrations extends ListRecords
{
    use CancelsBulkInvoices;

    protected static string $resource = RunRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
