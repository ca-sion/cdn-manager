<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProvisionElementResource;
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
                Tables\Actions\CreateAction::make(),
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
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
