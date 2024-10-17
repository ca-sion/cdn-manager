<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ContactCategory;
use Filament\Resources\Resource;
use App\Filament\Resources\ContactCategoryResource\Pages;

class ContactCategoryResource extends Resource
{
    protected static ?string $model = ContactCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Catégorie de contacts';

    protected static ?string $pluralModelLabel = 'Catégories de contacts';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $navigationGroup = 'Collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->maxLength(255),
                Forms\Components\ColorPicker::make('color')
                    ->label('Couleur'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Couleur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index'  => Pages\ListContactCategories::route('/'),
            'create' => Pages\CreateContactCategory::route('/create'),
            'edit'   => Pages\EditContactCategory::route('/{record}/edit'),
        ];
    }
}
