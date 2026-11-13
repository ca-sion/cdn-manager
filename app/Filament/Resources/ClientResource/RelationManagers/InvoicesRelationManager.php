<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Enums\InvoiceStatusEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Actions\InvoicePdfAction;
use App\Filament\Actions\SendInvoiceAction;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Actions\RelaunchInvoiceAction;
use App\Filament\Actions\SendInvoiceBulkAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Filament\Actions\DownloadInvoiceEmlAction;
use App\Filament\Actions\RelaunchInvoiceBulkAction;
use App\Filament\Actions\ResetInvoiceViewedAtAction;
use App\Filament\Actions\GenerateClientInvoiceAction;
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
                GenerateClientInvoiceAction::make()
                    ->label('Générer')
                    ->tooltip(fn (): ?string => $this->ownerRecord->invoicing_note)
                    ->clientIdUsing(fn () => $this->ownerRecord->id),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    SendInvoiceAction::make(),
                    RelaunchInvoiceAction::make(),
                    DownloadInvoiceEmlAction::make(),
                    DownloadInvoiceEmlAction::make('downloadInvoiceEmlRelaunch')->relaunch(),
                    ResetInvoiceViewedAtAction::make(),
                    DeleteAction::make(),
                ]),
                InvoicePdfAction::make(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    SendInvoiceBulkAction::make(),
                    RelaunchInvoiceBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->currentEdition());
    }
}
