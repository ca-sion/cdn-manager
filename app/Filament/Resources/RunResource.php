<?php

namespace App\Filament\Resources;

use App\Models\Run;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\RunRegistrationTypesEnum;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RunResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RunResource extends Resource
{
    protected static ?string $model = Run::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $modelLabel = 'Course';

    protected static ?string $pluralModelLabel = 'Courses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de base')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('distance')
                            ->label('Distance')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\TextInput::make('cost')
                            ->label('Coût')
                            ->numeric()
                            ->prefix('CHF'),
                        Forms\Components\Select::make('available_for_types')
                            ->label(' Disponible pour')
                            ->multiple()
                            ->options(RunRegistrationTypesEnum::class)
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Logistique et Limites')
                    ->schema([
                        Forms\Components\Repeater::make('start_blocs')
                            ->label('Blocs de départ')
                            ->schema([
                                Forms\Components\TextInput::make('label')->label('Nom du bloc')->required(),
                                Forms\Components\TextInput::make('time')->label('Heure')->type('time'),
                            ])
                            ->columns(2),
                        Forms\Components\DatePicker::make('registrations_deadline')
                            ->label('Délai d\'inscription'),
                        Forms\Components\TextInput::make('registrations_limit')
                            ->label('Limite d\'inscriptions')
                            ->numeric(),
                        Forms\Components\TextInput::make('registrations_number')
                            ->label('Nombre d\'inscrits actuel')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Codes et Provision')
                    ->schema([
                        Forms\Components\TextInput::make('datasport_code')
                            ->label('Code Datasport'),
                        Forms\Components\TextInput::make('code')
                            ->label('Code interne'),
                        Forms\Components\Toggle::make('accepts_voucher')
                            ->label('Accepte les vouchers')
                            ->inline(false),
                        Forms\Components\Select::make('provision_id')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance')
                    ->label('Dist.')
                    ->suffix(' km')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Prix')
                    ->money('CHF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_for_types')
                    ->label('Types')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? collect($state)->map(fn ($type) => RunRegistrationTypesEnum::from($type)->getLabel())->implode(', ') : $state),
                Tables\Columns\TextColumn::make('registrations_deadline')
                    ->label('Délai')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('registrations_limit')
                    ->label('Limite')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('registrations_number')
                    ->label('Inscrits')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('accepts_voucher')
                    ->label('Vouchers')
                    ->boolean(),
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
            'index'  => Pages\ListRuns::route('/'),
            'create' => Pages\CreateRun::route('/create'),
            'edit'   => Pages\EditRun::route('/{record}/edit'),
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
