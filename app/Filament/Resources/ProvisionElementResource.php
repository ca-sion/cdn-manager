<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;
use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Models\Contact;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProvisionElement;
use Filament\Resources\Resource;
use App\Enums\ProvisionElementStatusEnum;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProvisionElementResource\Pages;
use App\Services\PricingService;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Number;

class ProvisionElementResource extends Resource
{
    protected static ?string $model = ProvisionElement::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $pluralModelLabel = 'Éléments de prestation';

    protected static ?string $modelLabel = 'Élément de prestation';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Group::make([
                    Forms\Components\Select::make('edition_id')
                        ->label('Edition')
                        ->relationship('edition', 'year')
                        ->required(),
                    Forms\Components\Select::make('provision_id')
                        ->label('Prestation')
                        ->relationship('provision', 'name')
                        ->live()
                        ->required(),
                    Forms\Components\MorphToSelect::make('recipient')
                        ->label('Bénéficiaire')
                        ->types([
                            Forms\Components\MorphToSelect\Type::make(Contact::class)
                                ->titleAttribute('name'),
                            Forms\Components\MorphToSelect\Type::make(Client::class)
                                ->titleAttribute('name'),
                        ])
                        ->required()
                        ->hiddenOn(ProvisionElementsRelationManager::class),
                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->default('to_prepare')
                        ->options(ProvisionElementStatusEnum::class),
                ])->columns(4),
                Section::make('Champs')
                    ->columns(3)
                    ->live()
                    ->schema([
                        Forms\Components\TextInput::make('precision')
                            ->label('Précision')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_precision),
                        Forms\Components\TextInput::make('numeric_indicator')
                            ->label('Indicateur numérique')
                            ->numeric()
                            ->visible(fn (?Model $record) => $record?->provision?->has_numeric_indicator),
                        Forms\Components\TextInput::make('textual_indicator')
                            ->label('Indicateur textuel')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_textual_indicator),
                        Forms\Components\TextInput::make('goods_to_be_delivered')
                            ->label('Indicateur textuel')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_goods_to_be_delivered),
                        Forms\Components\Select::make('contact_id')
                            ->label('Contact')
                            ->relationship('contact', 'name')
                            ->visible(fn (?Model $record) => $record?->provision?->has_contact),
                        Forms\Components\TextInput::make('contact_text')
                            ->label('Contact')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_contact),
                        Forms\Components\TextInput::make('contact_location')
                            ->label('Lieu du contact')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_contact),
                        Forms\Components\DatePicker::make('contact_date')
                            ->label('Date du contact')
                            ->visible(fn (?Model $record) => $record?->provision?->has_contact),
                        Forms\Components\TimePicker::make('contact_time')
                            ->label('Heure du contact')
                            ->visible(fn (?Model $record) => $record?->provision?->has_contact),
                        Forms\Components\Select::make('media_status')
                            ->label('Statut du média')
                            ->options([
                                'requested' => 'Demandé',
                                'to_relaunch' => 'À relancer',
                                'relaunched' => 'Relancé',
                                'to_modify' => 'À modifier',
                                'received' => 'Reçu',
                                'physically_received' => 'Reçu physiquement',
                                'missing' => 'Manquant',
                            ])
                            ->visible(fn (?Model $record) => $record?->provision?->has_media),
                        SpatieMediaLibraryFileUpload::make('medias')
                            ->label('Médias')
                            ->collection('provision_elements')
                            ->multiple()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->imagePreviewHeight('50')
                            ->visible(fn (?Model $record) => $record?->provision?->has_media),
                        Forms\Components\TextInput::make('responsible')
                            ->label('Responsable')
                            ->maxLength(255)
                            ->visible(fn (?Model $record) => $record?->provision?->has_responsible),
                        Forms\Components\Select::make('dicastry_id')
                            ->label('Dicastère')
                            ->relationship('dicastry', 'name')
                            ->visible(fn (?Model $record) => $record?->provision?->has_responsible),
                        Forms\Components\Select::make('tracking_status')
                            ->label('Statut du média')
                            ->default('to_transmit')
                            ->options([
                                'to_transmit' => 'À transmettre',
                                'transmitted' => 'Transmis',
                                'suspended' => 'suspendu',
                            ])
                            ->visible(fn (?Model $record) => $record?->provision?->has_tracking),
                        Forms\Components\DatePicker::make('tracking_date')
                            ->label('Suivi le')
                            ->visible(fn (?Model $record) => $record?->provision?->has_tracking),
                        Forms\Components\Select::make('accreditation_type')
                            ->label('Type d\'accréditation du média')
                            ->default('media')
                            ->options([
                                'media' => 'Média',
                                'press' => 'Presse',
                                'organisation_cdn' => 'Organisation CDN',
                                'organisation_trail' => 'Organisation Trail',
                            ])
                            ->visible(fn (?Model $record) => $record?->provision?->has_accreditation),
                        Fieldset::make('Produit')
                            ->visible(fn (?Model $record) => $record?->provision?->has_product)
                            ->columns(5)
                            ->schema([
                                Forms\Components\Toggle::make('has_product')
                                    ->label('Produit')
                                    ->inline(false)
                                    ->live()
                                    ->default(true)
                                    ->visible(fn (?Model $record) => $record?->provision?->has_product),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\TextInput::make('cost')
                                    ->label('Prix')
                                    ->numeric()
                                    ->prefix('CHF')
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('syncCostFromProduct')
                                            ->label('Sync.')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, ?Model $record) {
                                                $set('cost', $record?->provision?->product?->cost);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Select::make('tax_rate')
                                    ->label('TVA')
                                    ->default(null)
                                    ->options([
                                        '8.1' => '8.1',
                                        '3.8' => '3.8',
                                        '2.6' => '2.1',
                                    ])
                                    ->suffix('%')
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('syncTaxRateFromProduct')
                                            ->label('Sync.')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, ?Model $record) {
                                                $set('tax_rate', $record?->provision?->product?->tax_rate);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Checkbox::make('include_vat')
                                    ->label('Inclure TVA')
                                    ->inline(false)
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('syncIncludeVatFromProduct')
                                            ->label('')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, ?Model $record) {
                                                $set('include_vat', $record?->provision?->product?->include_vat ? true : false);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Placeholder::make('product_price')
                                    ->label('Prix à facturer')
                                    ->content(function (Get $get): string {
                                        $price = PricingService::calculateCostPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                        $amount = PricingService::applyQuantity($price, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Placeholder::make('product_tax')
                                    ->label('TVA à facturer')
                                    ->content(function (Get $get): string {
                                        $tax = PricingService::calculateCostTax($get('cost'), $get('tax_rate'));
                                        $amount = PricingService::applyQuantity($tax, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Placeholder::make('product_net_price')
                                    ->label('Prix net')
                                    ->content(function (Get $get): string {
                                        $price = PricingService::calculateCostNetPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                        $amount = PricingService::applyQuantity($price, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                            ]),
                        Forms\Components\Select::make('vip_category')
                            ->label('Catégorie VIP')
                            ->options([
                                'individual' => 'individu',
                                'company' => 'Entreprise',
                                'sponsor' => 'Sponsor',
                                'partner' => 'Partenaire',
                                'town_council' => 'Conseil municipal',
                                'general_council' => 'Conseil général',
                                'states_council' => 'Conseil d\'état',
                                'national_council' => 'Conseil national',
                                'council_of_states' => 'Conseil des états',
                                'committee' => 'Comité (CDN)',
                                'committee_trail' => 'Comité (Trail)',
                                'trail' => 'Trail',
                                'swisslife' => 'Swisslife',
                            ])
                            ->visible(fn (?Model $record) => $record?->provision?->has_vip),
                        Forms\Components\TextInput::make('vip_invitation_number')
                            ->label('Nombre d\'invitation VIP')
                            ->numeric()
                            ->default(1)
                            ->visible(fn (?Model $record) => $record?->provision?->has_vip),
                        Forms\Components\Select::make('vip_response_status')
                            ->label('Réponse VIP')
                            ->placeholder('Sans réponse')
                            ->default(null)
                            ->options([
                                true => 'Inscrit',
                                false => 'Excusé',
                            ])
                            ->visible(fn (?Model $record) => $record?->provision?->has_vip),
                        Forms\Components\Textarea::make('vip_guests')
                            ->label('Liste invités')
                            ->visible(fn (?Model $record) => $record?->provision?->has_vip),
                    ]),
                Forms\Components\TextInput::make('note')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition.year')
                    ->label('Édition')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provision.name')
                    ->label('Prestation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient.name')
                    ->label('Bénéficiaire')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precision')
                    ->label('Précision')
                    ->searchable(),
                Tables\Columns\TextColumn::make('note')
                    ->searchable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->multiple()
                    ->options(ProvisionElementStatusEnum::class),
                Tables\Filters\SelectFilter::make('provision')
                    ->label('Prestation')
                    ->multiple()
                    ->relationship('provision', 'name'),
                Tables\Filters\SelectFilter::make('edition')
                    ->label('Édition')
                    ->multiple()
                    ->preload()
                    ->relationship('edition', 'year'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProvisionElements::route('/'),
            'create' => Pages\CreateProvisionElement::route('/create'),
            'edit' => Pages\EditProvisionElement::route('/{record}/edit'),
        ];
    }
}
