<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ReplicateAction;
use App\Filament\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Produits';

    protected static ?string $modelLabel = 'Produit';

    protected static ?string $navigationGroup = 'Collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom'),
                Forms\Components\TextInput::make('code')
                    ->label('Code'),
                Forms\Components\TextInput::make('cost')
                    ->label('Prix')
                    ->numeric()
                    ->inputMode('decimal')
                    ->prefix('CHF'),
                Forms\Components\Select::make('tax_rate')
                    ->label('TVA')
                    ->options([
                        '8.1' => '8.1',
                        '3.8' => '3.8',
                        '2.6' => '2.1',
                    ])
                    ->suffix('%'),
                Forms\Components\Checkbox::make('include_vat')
                    ->label('Inclure TVA')
                    ->inline(false),
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
                /*
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                */
                Tables\Columns\TextInputColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextInputColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Prix')
                    ->money('CHF', locale: 'fr_CH'),
                Tables\Columns\TextColumn::make('tax_rate')
                    ->label('TVA')
                    ->numeric(),
                    Tables\Columns\SelectColumn::make('tax_rate')
                    ->label('TVA')
                    ->options([
                        '8.1' => '8.1',
                        '3.8' => '3.8',
                        '2.6' => '2.1',
                    ]),

                    Tables\Columns\CheckboxColumn::make('include_vat')
                    ->label('Inclure TVA'),
                /*
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité'),
                */
                Tables\Columns\TextInputColumn::make('unit')
                    ->label('Unité'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    ReplicateAction::make()->successRedirectUrl(fn (Model $replica): string => route('filament.admin.resources.products.edit', [
                        'record' => $replica,
                    ])),
                    DeleteAction::make(),
                ]),
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
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
