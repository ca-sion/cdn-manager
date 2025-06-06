<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Factures';

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
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->dateTime('d.m.Y')
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
                    ->sortable(['status'])
                    ->state(fn (Model $record) => $record->status),
                Tables\Columns\SelectColumn::make('status')
                    ->label('')
                    ->options(InvoiceStatusEnum::class),
                Tables\Columns\TextColumn::make('paid_on')
                    ->label('Payé le')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Montant')
                    ->money('CHF', 0, 'fr_CH'),
                Tables\Columns\TextColumn::make('client_reference')
                    ->label('Référence client'),
                Tables\Columns\TextColumn::make('client.invoicingContactEmail')
                    ->label('Email'),
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
                Tables\Actions\Action::make('pdf')
                    ->url(fn (Model $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
                Tables\Actions\Action::make('ClientSendInvoice')
                    ->label('Envoyer')
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $record) {
                        $record->client?->notify(new ClientSendInvoice($record));
                        $record->status = InvoiceStatusEnum::Sent;
                        $record->save();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->currentEdition());
    }
}
