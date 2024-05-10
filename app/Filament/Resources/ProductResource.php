<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom'),
                Forms\Components\TextInput::make('code')
                    ->label('Code'),
                Forms\Components\TextInput::make('price')
                    ->label('Prix')
                    ->numeric()
                    ->inputMode('decimal')
                    ->suffix('CHF'),
                Forms\Components\Select::make('tax_rate')
                    ->label('TVA')
                    ->options([
                        '8.1' => '8.1',
                        '2.5' => '2.1',
                    ]),
                Forms\Components\TextInput::make('unit')
                    ->label('Unité'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prix')
                    ->money('CHF', locale: 'fr_CH'),
                Tables\Columns\TextColumn::make('tax_rate')
                    ->label('TVA')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
