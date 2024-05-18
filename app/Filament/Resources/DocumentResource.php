<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use Filament\Forms;
use Filament\Tables;
use App\Models\Document;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DocumentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\DocumentResource\RelationManagers;
use Filament\Forms\Get;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $pluralModelLabel = 'Documents';

    protected static ?string $modelLabel = 'Document';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('edition_id')
                    ->relationship('edition', 'year')
                    ->default(session('edition_id'))
                    ->required(),
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->hiddenOn(DocumentsRelationManager::class),
                Forms\Components\Select::make('type')
                    ->default('contract')
                    ->options([
                        'contract' => 'Contrat',
                        'invoice' => 'Facture',
                        'offer' => 'Offre',
                        'decision' => 'Décision',
                    ])
                    ->live()
                    ->required(),
                SpatieMediaLibraryFileUpload::make('medias')
                    ->label('Médias')
                    ->collection('documents')
                    ->customProperties(fn (Get $get) => ['type' => $get('type')])
                    ->multiple()
                    ->reorderable()
                    ->openable()
                    ->downloadable()
                    ->imagePreviewHeight('50')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('status')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('date'),
                Forms\Components\TextInput::make('validity_year_start')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('validity_year_end')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('note')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition_id')
                    ->label('Edition')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('validity_year_start')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('validity_year_end')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('note')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
