<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ClientResource;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('Fiche')
                ->url(fn (Model $record): string => $record->pdfLink)
                ->openUrlInNewTab()
                ->icon('heroicon-o-document'),
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
