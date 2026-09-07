<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SchoolResource;

class EditSchool extends EditRecord
{
    protected static string $resource = SchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
