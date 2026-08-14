<?php

namespace App\Filament\Resources;

use App\Models\Edition;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\EditionResource\Pages\EditEdition;
use App\Filament\Resources\EditionResource\Pages\ListEditions;
use App\Filament\Resources\EditionResource\Pages\CreateEdition;

class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Éditions';

    protected static ?string $modelLabel = 'Édition';

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('year')
                    ->label('Année')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom'),
                TextColumn::make('year')
                    ->label('Année'),
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
            'index'  => ListEditions::route('/'),
            'create' => CreateEdition::route('/create'),
            'edit'   => EditEdition::route('/{record}/edit'),
        ];
    }
}
