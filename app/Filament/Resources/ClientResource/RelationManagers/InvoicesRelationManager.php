<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function form(Form $form): Form
    {
        return InvoiceResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                Tables\Columns\TextColumn::make('edition.year'),
                Tables\Columns\TextColumn::make('number'),
                Tables\Columns\TextColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('generateInvoice')
                    ->label('Générer')
                    ->action(fn () => InvoiceService::generateInvoiceByClient($this->ownerRecord->id)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->url(fn (Model $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
