<?php

namespace App\Filament\Resources\RunRegistrationResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class RunRegistrationElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'runRegistrationElements';

    protected static ?string $title = 'Participants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')->label('Prénom')->required(),
                Forms\Components\TextInput::make('last_name')->label('Nom')->required(),
                Forms\Components\DatePicker::make('birthdate')->label('Date de naissance'),
                Forms\Components\Select::make('gender')->label('Genre')->options(['M' => 'M', 'F' => 'F']),
                Forms\Components\Select::make('run_id')
                    ->label('Course')
                    ->relationship('run', 'name')
                    ->required(),
                Forms\Components\TextInput::make('team')->label('Équipe'),
                Forms\Components\TextInput::make('voucher_code')->label('Code Voucher'),
                Forms\Components\Toggle::make('with_video')->label('Vidéo')->default(false),
                Forms\Components\Toggle::make('has_free_registration_fee')->label('Gratuit')->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('Prénom')->searchable(),
                Tables\Columns\TextColumn::make('last_name')->label('Nom')->searchable(),
                Tables\Columns\TextColumn::make('birthdate')->label('Né le')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('run.name')->label('Course'),
                Tables\Columns\TextColumn::make('team')->label('Équipe'),
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
