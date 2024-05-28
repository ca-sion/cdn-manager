<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
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
            ->columns([
                Tables\Columns\TextColumn::make('provision.name'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('precision')
                    ->label('Précision'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Montant')
                    ->state(fn (Model $record) => $record->has_product ? $record->price->amount('c') : null)
                    ->description(fn (Model $record) => $record->has_product && $record->price->netAmount('c') != $record->price->amount('c') ? $record->price->netAmount('c') : null),
                Tables\Columns\TextColumn::make('note'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
