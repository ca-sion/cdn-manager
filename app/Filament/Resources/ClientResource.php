<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Filament\Resources\ClientResource\RelationManagers\ClientProductsRelationManager;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Base')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nom'),
                            Forms\Components\TextInput::make('long_name')
                                ->label('Nom long'),
                            Forms\Components\Select::make('category_id')
                                ->label('Catégorie')
                                ->relationship('category', 'name'),
                            Forms\Components\Textarea::make('note')
                                ->label('Note'),
                        ]),
                    Tabs\Tab::make('Contact')
                        ->columns(12)
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('phone')
                                ->label('Téléphone')
                                ->tel()
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('website')
                                ->label('Site web')
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('address')
                                ->label('Adresse')
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('address_extension')
                                ->label('Adresse (complément)')
                                ->columnSpan(3),
                            Forms\Components\TextInput::make('postal_code')
                                ->label('Code postal')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('locality')
                                ->label('Localité')
                                ->columnSpan(3),
                            /*
                            Forms\Components\TextInput::make('country_code')
                                ->label('Pays'),
                            */
                        ]),
                    Tabs\Tab::make('Facturation')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('invoicing_email')
                                ->label('Email de facturation'),
                            Forms\Components\TextInput::make('ide')
                                ->label('CH-IDE'),
                            Forms\Components\TextInput::make('iban'),
                            Forms\Components\TextInput::make('iban_qr'),
                            Forms\Components\Textarea::make('invoicing_note')
                                ->label('Note')
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make('Style')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('logo')
                                ->label('Logo')
                                ->collection('logos')
                                ->image()
                                ->imagePreviewHeight('100'),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('long_name')
                    ->toggleable()
                    ->searchable(),
                /*Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo'),*/
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logos'),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_extension')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('locality')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
            ProvisionElementsRelationManager::class,
            ContactsRelationManager::class,
            DocumentsRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
