<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Helpers\AppHelper;
use Filament\Actions\Action;
use App\Models\RunRegistration;
use Filament\Actions\ActionGroup;
use App\Enums\EngagementStageEnum;
use Filament\Actions\DeleteAction;
use App\Enums\EngagementStatusEnum;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Actions\ClientPdfAction;
use App\Filament\Resources\ClientResource;
use App\Filament\Actions\SendMediaMissingAction;
use App\Filament\Actions\SendClientVouchersAction;
use App\Notifications\ClientAdvertiserFormCreated;
use App\Filament\Actions\GenerateClientInvoiceAction;
use App\Filament\Actions\GenerateRegistrationInvoiceAction;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ClientPdfAction::make()->label('Fiche')->color('gray'),
            ActionGroup::make([
                GenerateClientInvoiceAction::make(),
                GenerateRegistrationInvoiceAction::make()
                    ->label('Facturer les inscriptions de courses')
                    ->color('gray')
                    ->visible(fn (Model $record): bool => RunRegistration::where('client_id', $record->id)->exists()),
            ])
                ->label('Facturation')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('gray')
                ->button(),
            ActionGroup::make([
                Action::make('ClientAdvertiserFormCreated')
                    ->label('Envoyer la commande')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->action(fn (Model $record) => $record->notify(new ClientAdvertiserFormCreated)),
                SendClientVouchersAction::make(),
                SendMediaMissingAction::make(),
            ])
                ->label('Envois')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->button(),
            Action::make('ModifyStageStatus')
                ->label('Modifier le statut')
                ->icon('heroicon-o-briefcase')
                ->color('gray')
                ->schema(function (Model $record) {
                    $engagement = $record->currentEngagement;

                    return [
                        Select::make('stage')
                            ->label('Progression')
                            ->nullable()
                            ->options(EngagementStageEnum::class)
                            ->default($engagement?->stage ?? EngagementStageEnum::Prospect),
                        Select::make('status')
                            ->label('Statut')
                            ->nullable()
                            ->options(EngagementStatusEnum::class)
                            ->default($engagement?->status ?? EngagementStatusEnum::Idle),
                        TextInput::make('responsible')
                            ->label('Responsable')
                            ->nullable()
                            ->default($engagement?->responsible ?? null),
                    ];
                })
                ->action(function (Model $record, array $data) {
                    $engagement = $record->currentEngagement()->firstOrCreate([
                        'edition_id' => AppHelper::getCurrentEditionId(),
                    ]);

                    $engagement->stage = $data['stage'];
                    $engagement->status = $data['status'];
                    $engagement->responsible = $data['responsible'];
                    $engagement->save();

                    Notification::make()
                        ->title('Engagement mis à jour')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
            $this->getSaveFormAction()->formId('form'),
        ];
    }
}
