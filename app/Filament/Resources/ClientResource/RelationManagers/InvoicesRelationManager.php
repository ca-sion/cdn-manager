<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
            ->columns(InvoiceResource::columns(withClient: false))
            ->filters(InvoiceResource::filters())
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
