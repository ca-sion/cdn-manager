<?php

namespace App\Filament\Resources;

use App\Classes\Price;
use App\Models\Client;
use App\Models\Contact;
use Livewire\Component;
use App\Models\Provision;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Enums\MediaStatusEnum;
use Illuminate\Support\Number;
use App\Models\ProvisionElement;
use App\Services\PricingService;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Illuminate\Support\HtmlString;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ColumnGroup;
use App\Enums\ProvisionElementStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\MorphToSelect;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Actions\ExportMediaBulkAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Forms\Components\MorphToSelect\Type;
use App\Filament\Exports\ProvisionElementExporter;
use App\Notifications\ClientAdvertiserMediaMissing;
use App\Filament\Actions\SendVipInvitationBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ProvisionElementResource\Pages\EditProvisionElement;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;
use App\Filament\Resources\ProvisionElementResource\Pages\CreateProvisionElement;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ProvisionElementResource extends Resource
{
    protected static ?string $model = ProvisionElement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $modelLabel = 'Élément de prestations';

    protected static ?string $pluralModelLabel = 'Éléments de prestations';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Group::make([
                    Select::make('edition_id')
                        ->label('Edition')
                        ->relationship('edition', 'year')
                        ->default(session('edition_id'))
                        ->required(),
                    Select::make('provision_id')
                        ->label('Prestation')
                        ->relationship('provision', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    MorphToSelect::make('recipient')
                        ->label('Bénéficiaire')
                        ->types([
                            Type::make(Contact::class)
                                ->titleAttribute('name'),
                            Type::make(Client::class)
                                ->titleAttribute('name'),
                        ])
                        ->required()
                        ->hiddenOn(ProvisionElementsRelationManager::class),
                    Select::make('status')
                        ->label('Statut')
                        ->default(ProvisionElementStatusEnum::Confirmed)
                        ->options(ProvisionElementStatusEnum::class),
                ])->columns(4),
                Section::make('Champs')
                    ->columns(3)
                    ->live()
                    ->schema([
                        DatePicker::make('due_date')
                            ->label('Echéance')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_due_date : false),
                        TextInput::make('precision')
                            ->label('Précision')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_precision : false),
                        TextInput::make('numeric_indicator')
                            ->label('Indicateur numérique')
                            ->numeric()
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_numeric_indicator : false),
                        TextInput::make('textual_indicator')
                            ->label('Indicateur textuel')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_textual_indicator : false),
                        TextInput::make('goods_to_be_delivered')
                            ->label('Marchandise prévue')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_goods_to_be_delivered : false),
                        Select::make('contact_id')
                            ->label('Contact')
                            ->relationship('contact', 'name')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        TextInput::make('contact_text')
                            ->label('Contact')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        TextInput::make('contact_location')
                            ->label('Lieu du contact')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        DatePicker::make('contact_date')
                            ->label('Date du contact')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        TimePicker::make('contact_time')
                            ->label('Heure du contact')
                            ->seconds(false)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_contact : false),
                        Select::make('media_status')
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
                        TextInput::make('responsible')
                            ->label('Responsable')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_responsible : false),
                        Select::make('dicastry_id')
                            ->label('Dicastère')
                            ->relationship('dicastry', 'name')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_responsible : false),
                        Select::make('tracking_status')
                            ->label('Statut du média')
                            ->default('to_transmit')
                            ->options([
                                'to_transmit' => 'À transmettre',
                                'transmitted' => 'Transmis',
                                'suspended'   => 'suspendu',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_tracking : false),
                        DatePicker::make('tracking_date')
                            ->label('Suivi le')
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_tracking : false),
                        Select::make('accreditation_type')
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
                                Toggle::make('has_product')
                                    ->label('Produit')
                                    ->inline(false)
                                    ->live()
                                    ->default(true)
                                    ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_product : false),
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                TextInput::make('cost')
                                    ->label('Prix')
                                    ->numeric()
                                    ->prefix('CHF')
                                    ->hintAction(
                                        Action::make('syncCostFromProduct')
                                            ->label('Sync.')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, Get $get) {
                                                $set('cost', Provision::find($get('provision_id'))->product?->cost);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Select::make('tax_rate')
                                    ->label('TVA')
                                    ->options([
                                        '8.1' => '8.1',
                                        '3.8' => '3.8',
                                        '2.6' => '2.1',
                                    ])
                                    ->suffix('%')
                                    ->hintAction(
                                        Action::make('syncTaxRateFromProduct')
                                            ->label('Sync.')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, Get $get) {
                                                $set('tax_rate', Provision::find($get('provision_id'))->product?->tax_rate);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Checkbox::make('include_vat')
                                    ->label('Inclure TVA')
                                    ->inline(false)
                                    ->hintAction(
                                        Action::make('syncIncludeVatFromProduct')
                                            ->label('')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (Set $set, Get $get) {
                                                $set('include_vat', Provision::find($get('provision_id'))->product?->include_vat ? true : false);
                                            })
                                    )
                                    ->live()
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Placeholder::make('product_price')
                                    ->label('Prix à facturer')
                                    ->content(function (Get $get): string {
                                        $price = PricingService::calculateCostPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                        $amount = PricingService::applyQuantity($price, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Placeholder::make('product_tax')
                                    ->label('TVA à facturer')
                                    ->content(function (Get $get): string {
                                        $tax = PricingService::calculateCostTax($get('cost'), $get('tax_rate'));
                                        $amount = PricingService::applyQuantity($tax, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                                Placeholder::make('product_net_price')
                                    ->label('Prix net')
                                    ->content(function (Get $get): string {
                                        $price = PricingService::calculateCostNetPrice($get('cost'), $get('tax_rate'), $get('include_vat'));
                                        $amount = PricingService::applyQuantity($price, $get('quantity'));

                                        return Number::currency($amount, in: 'CHF', locale: 'fr_CH');
                                    })
                                    ->visible(fn (Get $get) => $get('has_product')),
                            ]),
                        Select::make('vip_category')
                            ->label('Catégorie VIP')
                            ->options([
                                'individual'        => 'Individu',
                                'company'           => 'Entreprise',
                                'sponsor'           => 'Sponsor',
                                'partner'           => 'Partenaire',
                                'town_council'      => 'Conseil municipal',
                                'general_council'   => 'Conseil général',
                                'states_council'    => 'Conseil d\'État',
                                'national_council'  => 'Conseil national',
                                'council_of_states' => 'Conseil des États',
                                'committee'         => 'Comité (CDN)',
                                'committee_trail'   => 'Comité (Trail)',
                                'trail'             => 'Trail',
                                'swisslife'         => 'Swisslife',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        TextInput::make('vip_invitation_number')
                            ->label('Nombre d\'invitation VIP')
                            ->numeric()
                            ->default(1)
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        Select::make('vip_response_status')
                            ->label('Réponse VIP')
                            ->placeholder('Sans réponse')
                            ->default(null)
                            ->options([
                                true  => 'Inscrit',
                                false => 'Excusé',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                        TagsInput::make('vip_guests')
                            ->label('Liste invités')
                            ->splitKeys([',', 'Tab'])
                            ->reorderable()
                            ->nestedRecursiveRules([
                                'min:3',
                                'max:255',
                            ])
                            ->visible(fn (Get $get) => $get('provision_id') ? Provision::find($get('provision_id'))->has_vip : false),
                    ]),
                TextInput::make('note')
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
                TextColumn::make('recipientContactEmail')
                    ->label('Email de contact')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('recipientVipContactEmail')
                    ->label('Email VIP')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('status_view')
                    ->label('Statut')
                    ->badge()
                    ->sortable(['status'])
                    ->state(fn (Model $record) => $record->status),
                SelectColumn::make('status')
                    ->label('')
                    ->options(ProvisionElementStatusEnum::class),

                TextColumn::make('precision')
                    ->label('Précision')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('numeric_indicator')
                    ->label('Indicateur num.')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('textual_indicator')
                    ->label('Indicateur')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('goods_to_be_delivered')
                    ->label('Marchandise')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->toggleable(),
                TextColumn::make('contact.phone')
                    ->label('Contact - Tél')
                    ->toggleable(),
                TextColumn::make('contact_text')
                    ->label('Contact')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact_location')
                    ->label('Lieu')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact_date')
                    ->label('Date')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('contact_time')
                    ->label('Heure')
                    ->time('H:i')
                    ->sortable()
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
                    ->sortable(['media_status'])
                    ->state(fn (Model $record) => $record->media_status),
                SelectColumn::make('media_status')
                    ->label('')
                    ->options(MediaStatusEnum::class),
                */
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('dicastry.name')
                    ->label('Dicastère')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tracking_status')
                    ->label('Statut (suivi)')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accreditation_type')
                    ->label('Type (accréditation)')
                    ->sortable()
                    ->toggleable(),
                ColumnGroup::make('VIP', [
                    TextColumn::make('vip_category')
                        ->label('Catégorie (VIP)')
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('vip_invitation_number')
                        ->label('Nombre d\'invitation')
                        ->summarize(Sum::make())
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('vip_response_status')
                        ->label('Réponse (VIP)')
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('vip_guests')
                        ->label('Invités')
                        ->badge()
                        ->separator(',')
                        ->formatStateUsing(function ($state) {
                            if (is_array($state)) {
                                return $state['name'] ?? $state['label'] ?? (count($state) ? implode(', ', array_map(fn ($item) => is_array($item) ? ($item['name'] ?? $item['label'] ?? json_encode($item)) : $item, $state)) : null);
                            }

                            return $state;
                        })
                        ->toggleable(),
                ]),
                TextColumn::make('note')
                    ->label('Note')
                    ->verticallyAlignStart()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('cost')
                    ->label('Montant')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn (float $state) => Price::of($state)->amount('c')))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('net_amount')
                    ->state(function (Model $record): ?float {
                        return $record->price->net_amount;
                    })
                    ->label('Prix net')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->toggleable(),
                TextColumn::make('amount')
                    ->state(function (Model $record): ?float {
                        return $record->price->amount;
                    })
                    ->label('Prix')
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
                SelectFilter::make('status')
                    ->label('Statut')
                    ->multiple()
                    ->options(ProvisionElementStatusEnum::class),
                SelectFilter::make('provision')
                    ->label('Prestation')
                    ->multiple()
                    ->preload()
                    ->relationship('provision', 'name'),
                SelectFilter::make('media_status')
                    ->label('Statut (média)')
                    ->multiple()
                    ->options(MediaStatusEnum::class),
                SelectFilter::make('client')
                    ->label('Client')
                    ->multiple()
                    ->preload()
                    ->relationship('client', 'name'),
                SelectFilter::make('edition')
                    ->label('Édition')
                    ->multiple()
                    ->preload()
                    ->relationship('edition', 'year'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ReplicateAction::make(),
                    DeleteAction::make(),
                    Action::make('frontEditLink')
                        ->label('Lien d\'édition client')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn (Model $record) => $record->client?->frontEditLink)
                        ->openUrlInNewTab(),
                    Action::make('pdfLink')
                        ->label('Fiche client')
                        ->icon('heroicon-o-document')
                        ->url(fn (Model $record) => $record->client?->pdfLink)
                        ->openUrlInNewTab(),
                    Action::make('ClientAdvertiserMediaMissing')
                        ->label('Envoyer (média manquant)')
                        ->icon('heroicon-o-envelope')
                        ->action(fn (Model $record) => $record->client?->notify(new ClientAdvertiserMediaMissing)),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('copyEmail')
                        ->label('Copier emails')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->action(function (Component $livewire, Collection $records) {
                            $clipboard = '';
                            foreach ($records as $record) {
                                $email = $record->recipientContactEmail;
                                $clipboard .= "$email\n";
                            }
                            $livewire->dispatch('copy-to-clipboard', $clipboard);
                        })
                        ->extraAttributes([
                            'x-on:copy-to-clipboard.window' => 'navigator.clipboard.writeText($event.detail)',
                        ]),
                    SendVipInvitationBulkAction::make(),
                    ExportMediaBulkAction::make(),
                    BulkAction::make('bulkEdit')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Select::make('status')
                                ->label('Statut')
                                ->options(ProvisionElementStatusEnum::class),
                            Select::make('media_status')
                                ->label('Statut du média')
                                ->options(MediaStatusEnum::class),
                            Select::make('edition_id')
                                ->label('Edition')
                                ->relationship('edition', 'year'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                foreach (collect($data)->keys() as $key) {
                                    if ($data[$key]) {
                                        $record->$key = $data[$key];
                                    }
                                }
                                $record->save();
                            }
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(ProvisionElementExporter::class)
                    ->columnMapping(false),
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
            'index'  => ListProvisionElements::route('/'),
            'create' => CreateProvisionElement::route('/create'),
            'edit'   => EditProvisionElement::route('/{record}/edit'),
        ];
    }
}
