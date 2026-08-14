<?php

namespace App\Filament\Resources\RunRegistrationResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;

class RunRegistrationElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'runRegistrationElements';

    protected static ?string $title = 'Participants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations personnelles')
                    ->schema([
                        TextInput::make('first_name')->label('Prénom')->required(),
                        TextInput::make('last_name')->label('Nom')->required(),
                        DatePicker::make('birthdate')->label('Date de naissance'),
                        Select::make('gender')
                            ->label('Sexe')
                            ->options([
                                'M' => 'Masculin (M)',
                                'F' => 'Féminin (F)',
                            ]),
                        TextInput::make('nationality')->label('Nationalité')->default('SUI'),
                        TextInput::make('email')->label('Email')->email(),
                        Select::make('run_id')
                            ->label('Course')
                            ->relationship('run', 'name'),
                        TextInput::make('bloc')->label('Bloc'),
                        TextInput::make('team')->label('Équipe'),
                        TextInput::make('voucher_code')->label('Code Voucher'),
                        Toggle::make('with_video')->label('Vidéo HD')->default(false),
                    ])->columns(3),

                Section::make('Spécificités Élite')
                    ->collapsible()
                    ->schema([
                        TextInput::make('address')->label('Adresse'),
                        TextInput::make('address_extension')->label('Complément'),
                        TextInput::make('postal_code')->label('Code Postal'),
                        TextInput::make('locality')->label('Localité'),
                        TextInput::make('country')->label('Pays'),
                        TextInput::make('iban')->label('IBAN'),
                        Textarea::make('payment_note')->label('Note de paiement'),
                        Toggle::make('has_free_registration_fee')->label('Frais d\'inscription offerts'),
                        Toggle::make('has_bonus_start')->label('Prime au départ'),
                        TextInput::make('bonus_start_amount')->label('Montant départ')->numeric()->prefix('CHF'),
                        TextInput::make('bonus_ranking_amount')->label('Montant classement')->numeric()->prefix('CHF'),
                        TextInput::make('bonus_arrival_amount')->label('Montant arrivée')->numeric()->prefix('CHF'),
                        Toggle::make('has_accommodation')->label('Hébergement'),
                        Toggle::make('accommodation_friday')->label('Logement Vendredi'),
                        Toggle::make('accommodation_saturday')->label('Logement Samedi'),
                        Textarea::make('accommodation_precision')->label('Précisions logement'),
                        Toggle::make('has_expense_reimbursement')->label('Remboursement de frais'),
                        Textarea::make('expense_reimbursement_precision')->label('Précisions remboursement'),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                TextColumn::make('first_name')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('birthdate')->label('Né le')->date('d.m.Y')->sortable(),
                TextColumn::make('gender')->label('Sexe'),
                TextColumn::make('run.name')->label('Course')->sortable(),
                TextColumn::make('team')->label('Équipe')->searchable(),
                IconColumn::make('with_video')->label('Vidéo')->boolean(),
                IconColumn::make('has_free_registration_fee')->label('Gratuit')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
