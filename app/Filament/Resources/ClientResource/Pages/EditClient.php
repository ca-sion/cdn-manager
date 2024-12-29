<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ClientResource;
use App\Notifications\ClientAdvertiserFormCreated;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('Fiche')
                ->color('gray')
                ->url(fn (Model $record): string => $record->pdfLink)
                ->openUrlInNewTab()
                ->icon('heroicon-o-document'),
            Actions\Action::make('ClientAdvertiserFormCreated')
                ->label('Envoyer commande')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn (Model $record) => $record->notify(new ClientAdvertiserFormCreated)),
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
