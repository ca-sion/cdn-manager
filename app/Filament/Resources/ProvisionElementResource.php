<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Classes\Price;
use App\Models\Client;
use App\Models\Contact;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Provision;
use Filament\Tables\Table;
use App\Enums\MediaStatusEnum;
use Illuminate\Support\Number;
use App\Models\ProvisionElement;
use App\Services\PricingService;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ColumnGroup;
use App\Enums\ProvisionElementStatusEnum;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Support\MediaStream;
use App\Filament\Exports\ProvisionElementExporter;
use App\Filament\Resources\ProvisionElementResource\Pages;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

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
                        ->default(session('edition_id'))
                        ->required(),
                    Forms\Components\Select::make('provision_id')
                        ->label('Prestation')
                        ->relationship('provision', 'name')
                        ->searchable()
                        ->preload()
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
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Echéance')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_due_date : false),
                        Forms\Components\TextInput::make('precision')
                            ->label('Précision')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_precision : false),
                        Forms\Components\TextInput::make('numeric_indicator')
                            ->label('Indicateur numérique')
                            ->numeric()
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_numeric_indicator : false),
                        Forms\Components\TextInput::make('textual_indicator')
                            ->label('Indicateur textuel')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_textual_indicator : false),
                        Forms\Components\TextInput::make('goods_to_be_delivered')
                            ->label('Marchandise prévue')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_goods_to_be_delivered : false),
                        Forms\Components\Select::make('contact_id')
                            ->label('Contact')
                            ->relationship('contact', 'name')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Forms\Components\TextInput::make('contact_text')
                            ->label('Contact')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Forms\Components\TextInput::make('contact_location')
                            ->label('Lieu du contact')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Forms\Components\DatePicker::make('contact_date')
                            ->label('Date du contact')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Forms\Components\TimePicker::make('contact_time')
                            ->label('Heure du contact')
                            ->seconds(false)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Forms\Components\Select::make('media_status')
                            ->label('Statut du média')
                            ->options(MediaStatusEnum::class)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_media : false),
                        SpatieMediaLibraryFileUpload::make('medias')
                            ->label('Médias')
                            ->collection('provision_elements')
                            ->multiple()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->imagePreviewHeight('50')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_media : false),
                        Forms\Components\TextInput::make('responsible')
                            ->label('Responsable')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_responsible : false),
                        Forms\Components\Select::make('dicastry_id')
                            ->label('Dicastère')
                            ->relationship('dicastry', 'name')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_responsible : false),
                        Forms\Components\Select::make('tracking_status')
                            ->label('Statut du média')
                            ->default('to_transmit')
                            ->options([
                                'to_transmit' => 'À transmettre',
                                'transmitted' => 'Transmis',
                                'suspended'   => 'suspendu',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_tracking : false),
                        Forms\Components\DatePicker::make('tracking_date')
                            ->label('Suivi le')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_tracking : false),
                        Forms\Components\Select::make('accreditation_type')
                            ->label('Type d\'accréditation du média')
                            ->default('media')
                            ->options([
                                'media'              => 'Média',
                                'press'              => 'Presse',
                                'organisation_cdn'   => 'Organisation CDN',
                                'organisation_trail' => 'Organisation Trail',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_accreditation : false),
                        Fieldset::make('Produit')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_product : false)
                            ->columns(5)
                            ->schema([
                                Forms\Components\Toggle::make('has_product')
                                    ->label('Produit')
                                    ->inline(false)
                                    ->live()
                                    ->default(true)
                                    ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_product : false),
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
                                            ->action(function (Set $set, Get $get) {
                                                $set('cost', Provision::find($get('provision_id'))->product?->cost);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Forms\Components\Select::make('tax_rate')
                                    ->label('TVA')
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
                                            ->action(function (Set $set, Get $get) {
                                                $set('tax_rate', Provision::find($get('provision_id'))->product?->tax_rate);
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
                                            ->action(function (Set $set, Get $get) {
                                                $set('include_vat', Provision::find($get('provision_id'))->product?->include_vat ? true : false);
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
                                'individual'        => 'Individu',
                                'company'           => 'Entreprise',
                                'sponsor'           => 'Sponsor',
                                'partner'           => 'Partenaire',
                                'town_council'      => 'Conseil municipal',
                                'general_council'   => 'Conseil général',
                                'states_council'    => 'Conseil d\'état',
                                'national_council'  => 'Conseil national',
                                'council_of_states' => 'Conseil des états',
                                'committee'         => 'Comité (CDN)',
                                'committee_trail'   => 'Comité (Trail)',
                                'trail'             => 'Trail',
                                'swisslife'         => 'Swisslife',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        Forms\Components\TextInput::make('vip_invitation_number')
                            ->label('Nombre d\'invitation VIP')
                            ->numeric()
                            ->default(1)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        Forms\Components\Select::make('vip_response_status')
                            ->label('Réponse VIP')
                            ->placeholder('Sans réponse')
                            ->default(null)
                            ->options([
                                true  => 'Inscrit',
                                false => 'Excusé',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        Forms\Components\Textarea::make('vip_guests')
                            ->label('Liste invités')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
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
                TextColumn::make('edition.year')
                    ->label('Édition')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('recipient.category.name')
                    ->label('Catégorie')
                    ->html()
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString('<span class="text-white text-xs font-medium me-2 px-2.5 py-0.5 rounded" style="background-color:'.$record->recipient?->category?->color.';">'.$record->recipient?->category?->name.'</span>'))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provision.name')
                    ->label('Prestation')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recipient.name')
                    ->label('Bénéficiaire')
                    ->searchable(),
                TextColumn::make('recipient.address')
                    ->label('Adresse')
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString("{$record->recipient?->address}<br>".($record->recipient?->address_extension ? "{$record->recipient?->address_extension}<br>" : null)."{$record->recipient?->postal_code} {$record->recipient?->locality}"))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('clientAdministrationEmail')
                    ->label('Email')
                    ->copyable(),
                TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable()
                    ->state(fn (Model $record) => $record->status),
                SelectColumn::make('status')
                    ->label('')
                    ->options(ProvisionElementStatusEnum::class),

                TextColumn::make('precision')
                    ->label('Précision')
                    ->searchable(),
                TextColumn::make('numeric_indicator')
                    ->label('Indicateur num.')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->toggleable(),
                TextColumn::make('textual_indicator')
                    ->label('Indicateur')
                    ->toggleable(),
                TextColumn::make('goods_to_be_delivered')
                    ->label('Marchandise')
                    ->toggleable(),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->toggleable(),
                TextColumn::make('contact_text')
                    ->label('Contact')
                    ->toggleable(),
                TextColumn::make('contact_location')
                    ->label('Lieu')
                    ->toggleable(),
                TextColumn::make('contact_date')
                    ->label('Date')
                    ->date('d.m.Y')
                    ->toggleable(),
                TextColumn::make('contact_time')
                    ->label('Heure')
                    ->time('H:i')
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('medias')
                    ->label('Média')
                    ->collection('provision_elements')
                    ->toggleable(),
                TextColumn::make('media_status')
                    ->label('Statut (média)')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                /*
                TextColumn::make('media_status_view')
                    ->label('Statut (média)')
                    ->badge()
                    ->sortable()
                    ->state(fn (Model $record) => $record->media_status),
                SelectColumn::make('media_status')
                    ->label('')
                    ->options(MediaStatusEnum::class),
                */
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->toggleable(),
                TextColumn::make('dicastry.name')
                    ->label('Dicastère')
                    ->toggleable(),
                TextColumn::make('tracking_status')
                    ->label('Statut (suivi)')
                    ->toggleable(),
                TextColumn::make('accreditation_type')
                    ->label('Type (accréditation)')
                    ->toggleable(),
                ColumnGroup::make('VIP', [
                    TextColumn::make('vip_category')
                        ->label('Catégorie (VIP)')
                        ->toggleable(),
                    TextColumn::make('vip_invitation_number')
                        ->label('Nombre d\'invitation')
                        ->summarize(Sum::make())
                        ->toggleable(),
                    TextColumn::make('vip_response_status')
                        ->label('Réponse (VIP)')
                        ->toggleable(),
                    TextColumn::make('vip_guests')
                        ->label('Invités')
                        ->toggleable(),
                ]),
                TextColumn::make('note')
                    ->label('Note')
                    ->verticallyAlignStart()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('cost')
                    ->label('Montant')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn (float $state) => Price::of($state)->amount('c')))
                    ->toggleable(),
                TextColumn::make('net_cost')
                    ->state(function (Model $record): ?float {
                        return $record->price->net_amount;
                    })
                    ->label('Montant net')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Supprimé')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Créé')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
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
                    ->preload()
                    ->relationship('provision', 'name'),
                Tables\Filters\SelectFilter::make('media_status')
                    ->label('Statut (média)')
                    ->multiple()
                    ->options(MediaStatusEnum::class),
                Tables\Filters\SelectFilter::make('client')
                    ->label('Client')
                    ->multiple()
                    ->preload()
                    ->relationship('client', 'name'),
                Tables\Filters\SelectFilter::make('edition')
                    ->label('Édition')
                    ->multiple()
                    ->preload()
                    ->relationship('edition', 'year'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ReplicateAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('export_medias')
                        ->label('Exporter les médias (.zip)')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (Collection $records) {
                            $downloads = $records->map(function ($record) {
                                $media = $record->getMedia('provision_elements')->first();
                                if ($media) {
                                    $mediaName = $record->recipient?->name ?? $media->name;
                                    $media->file_name = str()->slug($mediaName).'-'.$media->id.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION);
                                    $media->save();

                                    return $media;
                                }

                                return null;
                            });
                            $downloads = $downloads->filter();

                            return MediaStream::create('medias.zip')->addMedia($downloads);
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProvisionElementExporter::class)
                    ->columnMapping(false),
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
            'index'  => Pages\ListProvisionElements::route('/'),
            'create' => Pages\CreateProvisionElement::route('/create'),
            'edit'   => Pages\EditProvisionElement::route('/{record}/edit'),
        ];
    }
}
