<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Actions;
use App\Helpers\AppHelper;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
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
            Actions\Action::make('ClientMofidyStageStatus')
                ->label('Modifier le statut')
                ->icon('heroicon-o-briefcase')
                ->color('gray')
                ->form([
                    Select::make('stage')
                        ->label('Progression')
                        ->nullable()
                        ->options(EngagementStageEnum::class)
                        ->default(EngagementStageEnum::Prospect),
                    Select::make('status')
                        ->label('Statut')
                        ->nullable()
                        ->options(EngagementStatusEnum::class)
                        ->default(EngagementStatusEnum::Idle),
                ])
                ->action(function (Model $record, array $data) {
                    $engagement = $record->currentEngagement()->firstOrCreate([
                        'edition_id' => AppHelper::getCurrentEditionId(),
                    ]);

                    $engagement->stage = $data['stage'];
                    $engagement->status = $data['status'];
                    $engagement->save();

                    Notification::make()
                        ->title('Engagement mis à jour')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
