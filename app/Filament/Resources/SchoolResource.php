<?php

namespace App\Filament\Resources;

use App\Models\School;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\SchoolResource\Pages\EditSchool;
use App\Filament\Resources\SchoolResource\Pages\ListSchools;
use App\Filament\Resources\SchoolResource\Pages\CreateSchool;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?string $modelLabel = 'Établissement scolaire';

    protected static ?string $pluralModelLabel = 'Établissements scolaires';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informations de l\'établissement')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de l\'établissement')
                            ->placeholder('Ex: Centre scolaire de St-Guérin')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('postal_code')
                            ->label('Code postal')
                            ->placeholder('1950')
                            ->maxLength(10),
                        TextInput::make('locality')
                            ->label('Localité')
                            ->placeholder('Sion')
                            ->maxLength(255),
                        TextInput::make('country')
                            ->label('Pays')
                            ->default('SUI')
                            ->maxLength(10),
                        Select::make('client_id')
                            ->label('Client associé')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Les inscriptions de cette école seront automatiquement liées à ce client pour la facturation.'),
                        Toggle::make('is_active')
                            ->label('Actif / Disponible pour les inscriptions')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('postal_code')
                    ->label('NPA')
                    ->sortable(),
                TextColumn::make('locality')
                    ->label('Localité')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Client CRM associé')
                    ->placeholder('Non associé')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('run_registrations_count')
                    ->label('Inscriptions')
                    ->counts('runRegistrations')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d.m.Y H:i')
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'edit'   => EditSchool::route('/{record}/edit'),
        ];
    }
}
