<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextInputColumn;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Produits';

    protected static ?string $modelLabel = 'Produit';

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom'),
                TextInput::make('code')
                    ->label('Code'),
                TextInput::make('cost')
                    ->label('Prix')
                    ->numeric()
                    ->inputMode('decimal')
                    ->prefix('CHF'),
                Select::make('tax_rate')
                    ->label('TVA')
                    ->options([
                        '8.1' => '8.1',
                        '3.8' => '3.8',
                        '2.6' => '2.1',
                    ])
                    ->suffix('%'),
                Checkbox::make('include_vat')
                    ->label('Inclure TVA')
                    ->inline(false),
                TextInput::make('unit')
                    ->label('Unité'),
                Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                /*
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                */
                TextInputColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                TextColumn::make('cost')
                    ->label('Prix')
                    ->money('CHF', locale: 'fr_CH'),
                TextColumn::make('tax_rate')
                    ->label('TVA')
                    ->numeric(),
                SelectColumn::make('tax_rate')
                    ->label('TVA')
                    ->options([
                        '8.1' => '8.1',
                        '3.8' => '3.8',
                        '2.6' => '2.1',
                    ]),

                CheckboxColumn::make('include_vat')
                    ->label('Inclure TVA'),
                /*
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité'),
                */
                TextInputColumn::make('unit')
                    ->label('Unité'),
                ToggleColumn::make('is_active')
                    ->label('Actif')
                    ->extraAttributes(['style' => 'padding-top: 0;padding-bottom: 0;']),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Uniquement actifs')
                    ->query(fn (Builder $query): Builder => $query->active())
                    ->default(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ReplicateAction::make()->successRedirectUrl(fn (Model $replica): string => route('filament.admin.resources.products.edit', [
                        'record' => $replica,
                    ])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }
}
