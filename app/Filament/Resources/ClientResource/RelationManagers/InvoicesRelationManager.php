<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\RelationManagers\RelationManager;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Factures';

    public function form(Schema $schema): Schema
    {
        return InvoiceResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->defaultSort('number', 'desc')
            ->columns([
                TextColumn::make('edition.year')
                    ->label('Édition')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('number')
                    ->label('Numéro')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->sortable(),
                TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable(['status'])
                    ->state(fn (Model $record) => $record->status),
                SelectColumn::make('status')
                    ->label('')
                    ->options(InvoiceStatusEnum::class),
                TextColumn::make('paid_on')
                    ->label('Payé le')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Montant')
                    ->money('CHF', 0, 'fr_CH'),
                TextColumn::make('client_reference')
                    ->label('Référence client'),
                TextColumn::make('client.invoicingContactEmail')
                    ->label('Email'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('generateInvoice')
                    ->label('Générer')
                    ->tooltip(fn (): ?string => $this->ownerRecord->invoicing_note)
                    ->action(fn () => InvoiceService::generateInvoiceByClient($this->ownerRecord->id)),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->url(fn (Model $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
                Action::make('ClientSendInvoice')
                    ->label('Envoyer')
                    ->icon('heroicon-o-envelope')
                    ->action(function (Model $record) {
                        $record->client?->notify(new ClientSendInvoice($record));
                        $record->status = InvoiceStatusEnum::Sent;
                        $record->save();
                    })
                    ->requiresConfirmation(),
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->currentEdition());
    }
}
