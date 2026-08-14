<?php

namespace App\Filament\Resources\ContactResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use App\Filament\Imports\ContactImporter;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ContactResource;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->label('Importer')
                ->importer(ContactImporter::class),
        ];
    }
}
