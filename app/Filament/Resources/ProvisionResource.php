<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProvisionResource\Pages;
use App\Filament\Resources\ProvisionResource\RelationManagers;
use App\Models\Provision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProvisionResource extends Resource
{
    protected static ?string $model = Provision::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom'),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\TextInput::make('dicastry')
                    ->label('Dicastère'),
                Forms\Components\TextInput::make('type')
                    ->label('Type'),
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->hint('Optionnel')
                    ->relationship('product', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dicastry'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('product.name'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProvisions::route('/'),
            'create' => Pages\CreateProvision::route('/create'),
            'edit' => Pages\EditProvision::route('/{record}/edit'),
        ];
    }
}
