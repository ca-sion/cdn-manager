<?php

namespace App\Filament\Resources\RunRegistrationResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\Gender;
use Filament\Resources\RelationManagers\RelationManager;

class RunRegistrationElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'runRegistrationElements';

    protected static ?string $title = 'Participants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')->label('Prénom')->required(),
                        Forms\Components\TextInput::make('last_name')->label('Nom')->required(),
                        Forms\Components\DatePicker::make('birthdate')->label('Date de naissance'),
                        Forms\Components\Select::make('gender')
                            ->label('Sexe')
                            ->options([
                                'M' => 'Masculin (M)',
                                'F' => 'Féminin (F)',
                            ]),
                        Forms\Components\TextInput::make('nationality')->label('Nationalité')->default('SUI'),
                        Forms\Components\TextInput::make('email')->label('Email')->email(),
                        Forms\Components\Select::make('run_id')
                            ->label('Course')
                            ->relationship('run', 'name'),
                        Forms\Components\TextInput::make('bloc')->label('Bloc'),
                        Forms\Components\TextInput::make('team')->label('Équipe'),
                        Forms\Components\TextInput::make('voucher_code')->label('Code Voucher'),
                        Forms\Components\Toggle::make('with_video')->label('Vidéo HD')->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Spécificités Élite')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('address')->label('Adresse'),
                        Forms\Components\TextInput::make('address_extension')->label('Complément'),
                        Forms\Components\TextInput::make('postal_code')->label('Code Postal'),
                        Forms\Components\TextInput::make('locality')->label('Localité'),
                        Forms\Components\TextInput::make('country')->label('Pays'),
                        Forms\Components\TextInput::make('iban')->label('IBAN'),
                        Forms\Components\Textarea::make('payment_note')->label('Note de paiement'),
                        Forms\Components\Toggle::make('has_free_registration_fee')->label('Frais d\'inscription offerts'),
                        Forms\Components\Toggle::make('has_bonus_start')->label('Prime au départ'),
                        Forms\Components\TextInput::make('bonus_start_amount')->label('Montant départ')->numeric()->prefix('CHF'),
                        Forms\Components\TextInput::make('bonus_ranking_amount')->label('Montant classement')->numeric()->prefix('CHF'),
                        Forms\Components\TextInput::make('bonus_arrival_amount')->label('Montant arrivée')->numeric()->prefix('CHF'),
                        Forms\Components\Toggle::make('has_accommodation')->label('Hébergement'),
                        Forms\Components\Toggle::make('accommodation_friday')->label('Logement Vendredi'),
                        Forms\Components\Toggle::make('accommodation_saturday')->label('Logement Samedi'),
                        Forms\Components\Textarea::make('accommodation_precision')->label('Précisions logement'),
                        Forms\Components\Toggle::make('has_expense_reimbursement')->label('Remboursement de frais'),
                        Forms\Components\Textarea::make('expense_reimbursement_precision')->label('Précisions remboursement'),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Prénom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('birthdate')->label('Né le')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('gender')->label('Sexe'),
                Tables\Columns\TextColumn::make('run.name')->label('Course')->sortable(),
                Tables\Columns\TextColumn::make('team')->label('Équipe')->searchable(),
                Tables\Columns\IconColumn::make('with_video')->label('Vidéo')->boolean(),
                Tables\Columns\IconColumn::make('has_free_registration_fee')->label('Gratuit')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
