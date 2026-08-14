<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use App\Services\PricingService;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Filament\Support\Enums\TextSize;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ClientSendInvoice;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Notifications\ClientSendInvoiceRelaunch;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Filament\Actions\ExportInvoicesPdfBulkAction;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $pluralModelLabel = 'Factures';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'number', 'qr_reference', 'reference', 'client_reference'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn(InvoicesRelationManager::class),
                Select::make('status')
                    ->label('Statut')
                    ->default('draft')
                    ->options(InvoiceStatusEnum::class)
                    ->live()
                    ->required(),
                TextInput::make('title')
                    ->label('Titre')
                    ->default(fn () => InvoiceService::generateInvoiceTitle())
                    ->maxLength(255),
                TextInput::make('number')
                    ->label('Numéro de facture')
                    ->default(fn () => InvoiceService::generateInvoiceNumber())
                    ->hintAction(
                        Action::make('syncQrReference')
                            ->label('Générer')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (Set $set) {
                                $set('number', InvoiceService::generateInvoiceNumber());
                            })
                    )
                    ->maxLength(255),
                DatePicker::make('date')->label('Date'),
                DatePicker::make('due_date')->label('Echéance'),
                DatePicker::make('paid_on')->label('Payé le'),
                TextInput::make('reference')
                    ->label('Référence')
                    ->maxLength(255),
                TextInput::make('client_reference')
                    ->label('Référence pour le client')
                    ->maxLength(255),
                TextInput::make('qr_reference')
                    ->label('Référence QR')
                    ->hintAction(
                        Action::make('syncQrReference')
                            ->label('Générer')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (Get $get, Set $set) {
                                $get('number') ? $get('number') : $set('number', InvoiceService::generateInvoiceNumber());
                                $number = $get('number');
                                $set('qr_reference', QrPaymentReferenceGenerator::generate(null, $number));
                            })
                    )
                    ->live()
                    ->maxLength(255),
                Toggle::make('is_pro_forma')
                    ->label('Facture proforma ?')
                    ->default(false),
                Repeater::make('positions')
                    ->label('Positions')
                    ->addActionLabel('Ajouter une position')
                    ->columnSpanFull()
                    ->columns(6)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotals($get, $set);
                    })
                    ->deleteAction(
                        fn (Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                    )
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom'),
                        TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->default(1)
                            ->live(),
                        TextInput::make('cost')
                            ->label('Prix')
                            ->numeric()
                            ->prefix('CHF')
                            ->live(),
                        Select::make('tax_rate')
                            ->label('TVA')
                            ->default(null)
                            ->options([
                                '8.1' => '8.1',
                                '3.8' => '3.8',
                                '2.6' => '2.1',
                            ])
                            ->suffix('%')
                            ->live(),
                        Checkbox::make('include_vat')
                            ->label('Inclure TVA')
                            ->inline(false)
                            ->live(),
                        Placeholder::make('product_price')
                            ->label('Total')
                            ->content(function (Get $get): string {
                                $price = PricingService::calculateCostPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                $amount = PricingService::applyQuantity($price, $get('quantity'));

                                return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                            }),
                    ]),
                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->readOnly()
                    ->prefix('CHF')
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?Model $record): string => $record ? $record->total : 0),
                RichEditor::make('content')->label('Contenu'),
                Textarea::make('footer')->label('Pied de page'),
                Textarea::make('note')->label('Note'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable(['status'])
                    ->state(fn (Model $record) => $record->status),
                SelectColumn::make('status')
                    ->label('')
                    ->options(InvoiceStatusEnum::class),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('viewed_at')
                    ->label('Vu à')
                    ->since()
                    ->dateTimeTooltip('d.m.y H:i')
                    ->timezone('Europe/Zurich')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),
                TextColumn::make('number')
                    ->label('Numéro')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('qr_reference')
                    ->label('QR')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => '…'.substr($state, -9))
                    ->size(TextSize::ExtraSmall)
                    ->toggleable(),
                IconColumn::make('is_pro_forma')
                    ->label('Pro forma')
                    ->boolean()
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->falseIcon('')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Montant')
                    ->money('CHF', 0, 'fr_CH'),
                TextColumn::make('totalTax')
                    ->label('Taxes')
                    ->money('CHF', 0, 'fr_CH'),
                TextColumn::make('paid_on_view')
                    ->label('Payé le')
                    ->date('d.m.Y')
                    ->sortable(['paid_on'])
                    ->state(fn (Model $record) => $record->paid_on),
                TextInputColumn::make('paid_on')
                    ->label('Payé le')
                    ->rules(['date', 'nullable']),
                TextColumn::make('client.invoicingContactEmail')
                    ->label('Email')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->multiple()
                    ->options(InvoiceStatusEnum::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('ClientSendInvoice')
                        ->label('Envoyer')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Model $record) {
                            $record->client?->notify(new ClientSendInvoice($record));
                            $record->status = InvoiceStatusEnum::Sent;
                            $record->save();
                        })
                        ->requiresConfirmation(),
                    Action::make('ClientSendInvoiceRelauch')
                        ->label('Relancer')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Model $record) {
                            $record->client?->notify(new ClientSendInvoiceRelaunch($record));
                            $record->status = InvoiceStatusEnum::Relaunched;
                            $record->save();
                        })
                        ->requiresConfirmation(),
                    Action::make('ClientDownloadInvoice')
                        ->label('Télécharger')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Invoice $record): string => URL::signedRoute('invoices.eml', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    Action::make('ClientDownloadInvoiceRelauch')
                        ->label('Relancer')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (Invoice $record): string => URL::signedRoute('invoices.emlRelaunch', ['invoice' => $record]))
                        ->openUrlInNewTab(),
                    Action::make('ResetViewedAt')
                        ->label('Réinitialiser « Vu à »')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Model $record) {
                            $record->viewed_at = null;
                            $record->save();
                        })
                        ->requiresConfirmation(),
                ]),
                Action::make('pdf')
                    ->label('PDF')
                    ->url(fn (Invoice $record): string => $record->link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportInvoicesPdfBulkAction::make(),
                    BulkAction::make('ClientsSendInvoice')
                        ->label('Envoyer facture')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->client?->notify(new ClientSendInvoice($record));
                                $record->status = InvoiceStatusEnum::Sent;
                                $record->save();
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('ClientsSendInvoiceRelaunch')
                        ->label('Envoyer relance')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->client?->notify(new ClientSendInvoiceRelaunch($record));
                                $record->status = InvoiceStatusEnum::Relaunched;
                                $record->save();
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->currentEdition());
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
            'index'  => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit'   => EditInvoice::route('/{record}/edit'),
        ];
    }

    // This function updates totals based on the selected products and quantities
    public static function updateTotals(Get $get, Set $set): void
    {
        // Retrieve all positions
        $positions = collect($get('positions'));

        // Retrieve prices for all selected products
        $positionAmounts = $positions->map(function ($position) {
            $price = PricingService::calculateCostPrice(data_get($position, 'cost'), data_get($position, 'tax_rate'), data_get($position, 'include_vat'));
            $amount = PricingService::applyQuantity($price, data_get($position, 'quantity'));

            return $amount;
        });

        // Update the state with the new values
        $set('total', $positionAmounts->sum());
    }
}
