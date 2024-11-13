<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Contact;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\ContactResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $pluralModelLabel = 'Contacts';

    protected static ?string $modelLabel = 'Contact';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                TextInput::make('first_name')
                    ->label('Prénom')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required(),
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
                Select::make('role')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Fonction/Titre')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('department')
                    ->label('Département/Service')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('address')
                    ->label('Adresse')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('postal_code')
                    ->label('Code postal')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locality')
                    ->label('Localité')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country_code')
                    ->label('Pays')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salutation')
                    ->label('Salutation')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('language')
                    ->label('Langue')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Création')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    BulkAction::make('bulkEdit')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Select::make('category_id')
                                ->label('Catégorie')
                                ->relationship('category', 'name'),
                            TextInput::make('role')
                                ->label('Fonction/Titre'),
                            TextInput::make('department')
                                ->label('Département/Service'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                foreach (collect($data)->keys() as $key) {
                                    if ($data[$key]) {
                                        $record->$key = $data[$key];
                                    }
                                }
                                $record->save();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProvisionElementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
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
