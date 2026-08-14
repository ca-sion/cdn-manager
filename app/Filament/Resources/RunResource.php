<?php

namespace App\Filament\Resources;

use App\Models\Run;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use App\Enums\RunRegistrationType;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RunResource\Pages\EditRun;
use App\Filament\Resources\RunResource\Pages\ListRuns;
use App\Filament\Resources\RunResource\Pages\CreateRun;

class RunResource extends Resource
{
    protected static ?string $model = Run::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?string $modelLabel = 'Course';

    protected static ?string $pluralModelLabel = 'Courses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de base')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('distance')
                            ->label('Distance')
                            ->numeric()
                            ->suffix('km'),
                        TextInput::make('cost')
                            ->label('Coût')
                            ->numeric()
                            ->prefix('CHF'),
                        Select::make('available_for_types')
                            ->label('Disponible pour')
                            ->multiple()
                            ->options(RunRegistrationType::class)
                            ->preload(),
                    ])->columns(2),

                Section::make('Logistique et Limites')
                    ->schema([
                        Repeater::make('start_blocs')
                            ->label('Blocs de départ')
                            ->schema([
                                TextInput::make('label')->label('Nom du bloc')->required(),
                                TextInput::make('time')->label('Heure')->type('time'),
                            ])
                            ->columns(2),
                        DateTimePicker::make('registrations_deadline')
                            ->label('Délai d\'inscription'),
                        TextInput::make('registrations_limit')
                            ->label('Limite d\'inscriptions')
                            ->numeric(),
                        TextInput::make('registrations_number')
                            ->label('Nombre d\'inscrits actuel')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('Codes et Provision')
                    ->schema([
                        TextInput::make('datasport_code')
                            ->label('Code Datasport'),
                        TextInput::make('code')
                            ->label('Code interne'),
                        Toggle::make('accepts_voucher')
                            ->label('Accepte les vouchers')
                            ->inline(false),
                        Select::make('provision_id')
                            ->label('Prestation liée')
                            ->relationship('provision', 'name')
                            ->searchable()
                            ->preload(),
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
                TextColumn::make('distance')
                    ->label('Dist.')
                    ->suffix(' km')
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('Prix')
                    ->money('CHF')
                    ->sortable(),
                TextColumn::make('available_for_types')
                    ->label('Types')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? collect($state)->map(fn ($type) => RunRegistrationType::tryFrom($type)?->getLabel() ?? $type)->implode(', ') : $state),
                TextColumn::make('registrations_deadline')
                    ->label('Délai')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('registrations_limit')
                    ->label('Limite')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('registrations_number')
                    ->label('Inscrits')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('accepts_voucher')
                    ->label('Vouchers')
                    ->boolean(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index'  => ListRuns::route('/'),
            'create' => CreateRun::route('/create'),
            'edit'   => EditRun::route('/{record}/edit'),
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
