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
use App\Filament\Resources\ContactCategoryResource\Pages\ListContactCategories;
use App\Filament\Resources\ContactCategoryResource\Pages\CreateContactCategory;
use App\Filament\Resources\ContactCategoryResource\Pages\EditContactCategory;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ContactCategory;
use Filament\Resources\Resource;
use App\Filament\Resources\ContactCategoryResource\Pages;

class ContactCategoryResource extends Resource
{
    protected static ?string $model = ContactCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Catégorie de contacts';

    protected static ?string $pluralModelLabel = 'Catégories de contacts';

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
            'index'  => ListContactCategories::route('/'),
            'create' => CreateContactCategory::route('/create'),
            'edit'   => EditContactCategory::route('/{record}/edit'),
        ];
    }
}
