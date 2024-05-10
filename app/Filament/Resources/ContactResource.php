<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                TextInput::make('first_name')
                    ->label('Prénom')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Prénom'),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/'),
                TextInput::make('role')
                    ->label('Fonction/Titre'),
                TextInput::make('department')
                    ->label('Département/Service'),
                TextInput::make('address')
                    ->label('Adresse'),
                TextInput::make('postal_code')
                    ->label('Code postal'),
                TextInput::make('locality')
                    ->label('Localité'),
                Select::make('country_code')
                    ->label('Pays')
                    ->options(['CH', 'FR', 'DE', 'IT']),
                TextInput::make('salutation')
                    ->label('Salutation'),
                Select::make('language')
                    ->label('Langue')
                    ->options(['fr', 'de', 'en', 'it']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom'),
                TextColumn::make('email')
                    ->label('Email'),
                TextColumn::make('phone')
                    ->label('Téléphone'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
