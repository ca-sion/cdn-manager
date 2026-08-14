<?php

namespace App\Filament\Resources;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use App\Models\ProvisionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColorColumn;
use Filament\Forms\Components\ColorPicker;
use App\Filament\Resources\ProvisionCategoryResource\Pages\EditProvisionCategory;
use App\Filament\Resources\ProvisionCategoryResource\Pages\CreateProvisionCategory;
use App\Filament\Resources\ProvisionCategoryResource\Pages\ListProvisionCategories;

class ProvisionCategoryResource extends Resource
{
    protected static ?string $model = ProvisionCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Catégorie de prestation';

    protected static ?string $pluralModelLabel = 'Catégories de prestations';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

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
            'index'  => ListProvisionCategories::route('/'),
            'create' => CreateProvisionCategory::route('/create'),
            'edit'   => EditProvisionCategory::route('/{record}/edit'),
        ];
    }
}
