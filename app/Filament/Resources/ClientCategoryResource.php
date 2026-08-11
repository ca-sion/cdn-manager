<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClientCategoryResource\Pages\ListClientCategories;
use App\Filament\Resources\ClientCategoryResource\Pages\CreateClientCategory;
use App\Filament\Resources\ClientCategoryResource\Pages\EditClientCategory;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ClientCategory;
use Filament\Resources\Resource;
use App\Filament\Resources\ClientCategoryResource\Pages;

class ClientCategoryResource extends Resource
{
    protected static ?string $model = ClientCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Catégorie de client';

    protected static ?string $pluralModelLabel = 'Catégories de clients';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string | \UnitEnum | null $navigationGroup = 'Collections';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->maxLength(255),
                ColorPicker::make('color')
                    ->label('Couleur'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                ColorColumn::make('color')
                    ->label('Couleur')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index'  => ListClientCategories::route('/'),
            'create' => CreateClientCategory::route('/create'),
            'edit'   => EditClientCategory::route('/{record}/edit'),
        ];
    }
}
