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
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\Column;
use Filament\Support\Enums\TextSize;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Actions\InvoicePdfAction;
use Filament\Forms\Components\Placeholder;
use App\Filament\Actions\SendInvoiceAction;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Actions\RelaunchInvoiceAction;
use App\Filament\Actions\SendInvoiceBulkAction;
use Filament\Tables\Enums\RecordActionsPosition;
use App\Filament\Actions\DownloadInvoiceEmlAction;
use App\Filament\Actions\RelaunchInvoiceBulkAction;
use App\Filament\Actions\ResetInvoiceViewedAtAction;
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
                    ->unique(ignoreRecord: true)
                    ->hintAction(
                        Action::make('syncNumber')
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
                        Hidden::make('provision_element_id'),
                        Hidden::make('run_registration_element_ids'),
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

    /**
     * Colonnes partagées avec le RelationManager des factures d'un client.
     *
     * @return array<int, Column>
     */
    public static function columns(bool $withClient = true): array
    {
        return array_values(array_filter([
            TextColumn::make('status_view')
                ->label('Statut')
                ->badge()
                ->sortable(['status'])
                ->state(fn (Model $record) => $record->status),
            SelectColumn::make('status')
                ->label('')
                ->options(InvoiceStatusEnum::class),
            TextColumn::make('type')
                ->label('Type')
                ->badge()
                ->state(fn (Model $record) => Invoice::typeLabels()[$record->type()])
                ->color(fn (string $state) => match ($state) {
                    'Courses'  => 'info',
                    'Proforma' => 'gray',
                    default    => 'primary',
                })
                ->toggleable(),
            TextColumn::make('date')
                ->label('Date')
                ->date()
                ->sortable()
                ->toggleable(),
            TextColumn::make('viewed_at')
                ->label('Vu à')
                ->since()
                ->dateTimeTooltip('d.m.y H:i')
                ->timezone('Europe/Zurich')
                ->sortable()
                ->toggleable(),
            $withClient ? TextColumn::make('client.name')
                ->label('Client')
                ->searchable() : null,
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
                ->money(),
            TextColumn::make('totalTax')
                ->label('Taxes')
                ->money(),
            TextColumn::make('paid_on_view')
                ->label('Payé le')
                ->date()
                ->sortable(['paid_on'])
                ->state(fn (Model $record) => $record->paid_on),
            TextInputColumn::make('paid_on')
                ->label('Payé le')
                ->rules(['date', 'nullable']),
            TextColumn::make('client.invoicingContactEmail')
                ->label('Email')
                ->toggleable(),
        ]));
    }

    /**
     * @return array<int, BaseFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('type')
                ->label('Type')
                ->options(Invoice::typeLabels())
                ->query(function (Builder $query, array $data): Builder {
                    $notProforma = fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma');

                    return match ($data['value'] ?? null) {
                        'proforma'    => $query->where('is_pro_forma', true),
                        'courses'     => $query->where($notProforma)->runInvoices(),
                        'prestations' => $query->where($notProforma)->whereNotIn('id', Invoice::runInvoices()->select('id')),
                        default       => $query,
                    };
                }),
            SelectFilter::make('status')
                ->label('Statut')
                ->multiple()
                ->options(InvoiceStatusEnum::class),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->filters(static::filters())
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    SendInvoiceAction::make(),
                    RelaunchInvoiceAction::make(),
                    DownloadInvoiceEmlAction::make(),
                    DownloadInvoiceEmlAction::make('downloadInvoiceEmlRelaunch')->relaunch(),
                    ResetInvoiceViewedAtAction::make(),
                ]),
                InvoicePdfAction::make(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportInvoicesPdfBulkAction::make(),
                    SendInvoiceBulkAction::make(),
                    RelaunchInvoiceBulkAction::make(),
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
