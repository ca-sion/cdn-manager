<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use App\Services\PricingService;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\InvoiceResource\Pages;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $pluralModelLabel = 'Factures';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'number', 'qr_reference', 'reference', 'client_reference'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn(InvoicesRelationManager::class),
                Forms\Components\Select::make('status')
                    ->default('draft')
                    ->options(InvoiceStatusEnum::class)
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->default(fn () => InvoiceService::generateInvoiceTitle())
                    ->maxLength(255),
                Forms\Components\TextInput::make('number')
                    ->default(fn () => InvoiceService::generateInvoiceNumber())
                    ->hintAction(
                        Forms\Components\Actions\Action::make('syncQrReference')
                            ->label('Générer')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (Set $set) {
                                $set('number', InvoiceService::generateInvoiceNumber());
                            })
                    )
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date'),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('client_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('qr_reference')
                    ->hintAction(
                        Forms\Components\Actions\Action::make('syncQrReference')
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
                Forms\Components\Toggle::make('is_pro_forma')
                    ->default(false),
                Forms\Components\Repeater::make('positions')
                    ->columnSpanFull()
                    ->columns(6)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotals($get, $set);
                    })
                    ->deleteAction(
                        fn (Forms\Components\Actions\Action $action) => $action->after(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                    )
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('nom'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->default(1)
                            ->live(),
                        Forms\Components\TextInput::make('cost')
                            ->label('Prix')
                            ->numeric()
                            ->prefix('CHF')
                            ->live(),
                        Forms\Components\Select::make('tax_rate')
                            ->label('TVA')
                            ->default(null)
                            ->options([
                                '8.1' => '8.1',
                                '3.8' => '3.8',
                                '2.6' => '2.1',
                            ])
                            ->suffix('%')
                            ->live(),
                        Forms\Components\Checkbox::make('include_vat')
                            ->label('Inclure TVA')
                            ->inline(false)
                            ->live(),
                        Forms\Components\Placeholder::make('product_price')
                            ->label('Total')
                            ->content(function (Get $get): string {
                                $price = PricingService::calculateCostPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                $amount = PricingService::applyQuantity($price, $get('quantity'));

                                return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                            }),
                    ]),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->readOnly()
                    ->prefix('CHF')
                    ->dehydrated(false),
                Forms\Components\RichEditor::make('content'),
                Forms\Components\Textarea::make('footer'),
                Forms\Components\Textarea::make('note'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable()
                    ->state(fn (Model $record) => $record->status),
                Tables\Columns\SelectColumn::make('status')
                    ->label('')
                    ->options(InvoiceStatusEnum::class),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Numéro')
                    ->sortable(),
                Tables\Columns\TextColumn::make('qr_reference')
                    ->label('QR')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state) => '…'.substr($state, -9))
                    ->size(TextColumnSize::ExtraSmall),
                Tables\Columns\TextColumn::make('total')
                    ->label('Montant')
                    ->money('CHF', 0, 'fr_CH'),
                Tables\Columns\TextColumn::make('totalTax')
                    ->label('Taxes')
                    ->money('CHF', 0, 'fr_CH'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->url(fn (Invoice $record): string => $record->link)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
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
