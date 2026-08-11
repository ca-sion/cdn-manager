<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Resources\DocumentResource\Pages\EditDocument;
use Filament\Forms;
use Filament\Tables;
use App\Models\Document;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\DocumentResource\Pages;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ClientResource\RelationManagers\DocumentsRelationManager;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $pluralModelLabel = 'Documents';

    protected static ?string $modelLabel = 'Document';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('edition_id')
                    ->relationship('edition', 'year')
                    ->default(session('edition_id'))
                    ->required(),
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->hiddenOn(DocumentsRelationManager::class),
                Select::make('type')
                    ->default('contract')
                    ->options([
                        'contract' => 'Contrat',
                        'invoice'  => 'Facture',
                        'offer'    => 'Offre',
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
                TextInput::make('name')
                    ->label('Nom ou titre')
                    ->maxLength(255),
                TextInput::make('status')
                    ->label('Statut')
                    ->maxLength(255),
                DatePicker::make('date')
                    ->label('Date')
                    ->default(now()),
                TextInput::make('validity_year_start')
                    ->label('Année de début')
                    ->maxLength(4),
                TextInput::make('validity_year_end')
                    ->label('Année de fin')
                    ->maxLength(4),
                TextInput::make('note')
                    ->label('Note')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('edition.year')
                    ->label('Edition')
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type'),
                TextColumn::make('status')
                    ->label('Statut'),
                TextColumn::make('date')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('validity_year_start')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('validity_year_end')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index'  => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit'   => EditDocument::route('/{record}/edit'),
        ];
    }
}
