<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\DicastryResource\Pages\ListDicastries;
use App\Filament\Resources\DicastryResource\Pages\CreateDicastry;
use App\Filament\Resources\DicastryResource\Pages\EditDicastry;
use Filament\Forms;
use Filament\Tables;
use App\Models\Dicastry;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\DicastryResource\Pages;

class DicastryResource extends Resource
{
    protected static ?string $model = Dicastry::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Dicastères';

    protected static ?string $modelLabel = 'Dicastère';

    protected static string | \UnitEnum | null $navigationGroup = 'Collections';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom'),
                TextInput::make('description')
                    ->label('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description'),
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
            'index'  => ListDicastries::route('/'),
            'create' => CreateDicastry::route('/create'),
            'edit'   => EditDicastry::route('/{record}/edit'),
        ];
    }
}
