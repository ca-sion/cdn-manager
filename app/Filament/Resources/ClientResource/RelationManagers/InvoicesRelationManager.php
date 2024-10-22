<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
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
            ->defaultSort('number', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('edition.year')
                    ->label('Édition')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Numéro')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable()
                    ->state(fn (Model $record) => $record->status),
                Tables\Columns\SelectColumn::make('status')
                    ->label('')
                    ->options(InvoiceStatusEnum::class),
                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Payé le')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_reference')
                    ->label('Référence client'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('generateInvoice')
                    ->label('Générer')
                    ->tooltip(fn (): ?string => $this->ownerRecord->invoicing_note)
                    ->action(fn () => InvoiceService::generateInvoiceByClient($this->ownerRecord->id)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->url(fn (Model $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
                Tables\Actions\Action::make('ClientSendInvoice')
                    ->label('Envoyer')
                    ->icon('heroicon-o-envelope')
                    ->action(fn (Model $record) => $record->client?->notify(new ClientSendInvoice($record))),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
