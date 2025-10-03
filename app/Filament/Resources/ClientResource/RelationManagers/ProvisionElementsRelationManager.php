<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use App\Models\Edition;
use Filament\Forms\Form;
use App\Helpers\AppHelper;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Actions\ExportMediaBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProvisionElementResource;
use App\Filament\Actions\SendVipInvitationBulkAction;
use Filament\Resources\RelationManagers\RelationManager;

class ProvisionElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'provisionElements';

    protected static ?string $title = 'Prestations';

    public function form(Form $form): Form
    {
        return ProvisionElementResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order_column')
            ->defaultSort('order_column')
            ->columns([
                TextColumn::make('provision.name'),
                TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable(['status'])
                    ->state(fn (Model $record) => $record->status),
                Tables\Columns\SelectColumn::make('status')
                    ->label('')
                    ->options(ProvisionElementStatusEnum::class),
                TextColumn::make('precision')
                    ->label('Précision'),
                TextColumn::make('price')
                    ->label('Montant')
                    ->state(fn (Model $record) => $record->has_product ? $record->price->amount('c') : null)
                    ->description(fn (Model $record) => $record->has_product && $record->price->netAmount('c') != $record->price->amount('c') ? $record->price->netAmount('c') : null),
                TextColumn::make('vip_category')
                    ->label('Catégorie (VIP)')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('vip_invitation_number')
                    ->label('Nombre d\'invitation')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('note')
                    ->label('Note')
                    ->verticallyAlignStart()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->icon('heroicon-o-plus'),
                Tables\Actions\Action::make('copy_previous')
                    ->label("Reprendre de l'édition précédente")
                    ->icon('heroicon-o-clipboard-document-list')
                    ->requiresConfirmation()
                    ->modalHeading('Reprendre les prestations')
                    ->modalDescription("Êtes-vous sûr de vouloir copier les prestations de l'édition précédente ? Les prestations existantes pour l'édition actuelle ne seront pas affectées.")
                    ->action(function (RelationManager $livewire) {
                        $client = $livewire->getOwnerRecord();
                        $currentEdition = Edition::find(AppHelper::getCurrentEditionId());

                        if (! $currentEdition) {
                            Notification::make()->title('Aucune édition actuelle définie')->warning()->send();

                            return;
                        }

                        $previousEdition = Edition::where('year', '<', $currentEdition->year)
                            ->orderBy('year', 'desc')
                            ->first();

                        if (! $previousEdition) {
                            Notification::make()->title('Aucune édition précédente trouvée')->warning()->send();

                            return;
                        }

                        $provisionsToCopy = $client->provisionElements()
                            ->where('edition_id', $previousEdition->id)
                            ->get();

                        if ($provisionsToCopy->isEmpty()) {
                            Notification::make()->title("Aucune prestation à copier depuis l'édition {$previousEdition->year}")->info()->send();

                            return;
                        }

                        foreach ($provisionsToCopy as $provision) {
                            $newProvision = $provision->replicate([
                                'edition_id', 'client_id',
                            ]);
                            $newProvision->fill([
                                'edition_id' => $currentEdition->id,
                                'status'     => ProvisionElementStatusEnum::Confirmed,
                            ]);
                            $client->provisionElements()->save($newProvision);
                        }

                        Notification::make()->title('Prestations copiées')->body("{$provisionsToCopy->count()} prestations ont été copiées depuis l'édition {$previousEdition->year}.")->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    SendVipInvitationBulkAction::make(),
                    ExportMediaBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->currentEdition()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
