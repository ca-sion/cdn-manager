<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Contact;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Provision;
use App\Models\ProvisionElement;
use App\Services\PricingService;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;

class AdvertiserForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Prestations')
                        ->schema([
                            Placeholder::make('Annonce journalistique')
                                        ->label('')
                                        ->content(new HtmlString('Sélectionner les prestations qui vous conviennent dans les listes ci-après.')),
                            Section::make('Anonce journalistique')
                                ->description(new HtmlString('Annonce dans le Journal de la Course de Noël et du Trail des Châteaux, édité à plus de 40 000 exemplaires, à paraître dans le Nouvelliste du NN novembre et distribué dans les districts de Sion, d’Hérens et de Conthey.<br><br>Consulter <a href="/docs/Dimensions_encarté_NF.pdf" class="underline text-primary-600 hover:text-primary-500">mise en page des emplacements</a> pour choisir votre emplacement et les dimensions de votre annonce.'))
                                ->schema([
                                    CheckboxList::make('journal_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_journal_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::where('category_id', setting('advertiser_form_journal_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->net_price ? $item->product?->price->net_price.' CHF' : null]))
                                        ->columns(2)
                                        ->live(),
                                ]),
                            Section::make('Banderoles')
                                ->schema([
                                    CheckboxList::make('banner_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_banner_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::all()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->net_price ? $item->product?->price->net_price.' CHF' : null]))
                                        ->live(),
                                ]),
                            Section::make('Écran dans la tente principale')
                                ->schema([
                                    CheckboxList::make('screen_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_screen_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::all()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->net_price.' CHF (+TVA)']))
                                        ->live(),
                                ]),
                            Section::make('Don d\'honneur')
                                ->description('Crédité dans l\'encarté du Nouvelliste')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('donnation_provision_amount')
                                        ->label('Montant')
                                        ->helperText('Le montant n\'est pas soumis à la TVA')
                                        ->numeric()
                                        ->suffix('CHF')
                                        ->maxLength(255),
                                    TextInput::make('donnation_provision_mention')
                                        ->label('Mention dans l\'encarté à côté du montant')
                                        ->helperText('Mentionner si anynyme')
                                        ->maxLength(255)
                                        ->live(),
                                ]),
                        ]),
                    Wizard\Step::make('Données de base')
                        ->schema([
                            Section::make('Annonceur')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nom de l\'annonceur')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    TextInput::make('address')
                                        ->label('Adresse')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 6]),
                                    TextInput::make('postal_code')
                                        ->label('Code postal')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 2]),
                                    TextInput::make('locality')
                                        ->label('Localité')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 4]),
                                ]),
                            Section::make('Personne de contact')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('contact.first_name')
                                        ->label('Prénom')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('contact.last_name')
                                        ->label('Nom de famille')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('contact.email')
                                        ->label('Email')
                                        ->required()
                                        ->email()
                                        ->maxLength(255)
                                        ->live(),
                                    TextInput::make('email')
                                        ->hidden()
                                        ->formatStateUsing(fn (Get $get) => $get('contacts.email')),
                                ]),
                            Section::make('Adresse de facturation')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('invoicing_email')
                                        ->label('Email')
                                        ->required()
                                        ->email()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    TextInput::make('invoicing_address')
                                        ->label('Adresse')
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 3]),
                                    TextInput::make('invoicing_address_extension')
                                        ->label('Complément d\'adresse')
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 3]),
                                    TextInput::make('invoicing_postal_code')
                                        ->label('Code postal')
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 2]),
                                    TextInput::make('invoicing_locality')
                                        ->label('Localité')
                                        ->maxLength(255)
                                        ->columnSpan(['md' => 4]),
                                ]),
                        ]),
                        Wizard\Step::make('Récapitulatif')
                            ->schema([
                                    Placeholder::make('total')
                                        ->label(new HtmlString('<div class="format"><h2>Total qui sera facturé</h2></div>'))
                                        ->content(function (Get $get) {
                                            $provisionIds = collect($get('journal_provisions'))->merge($get('screen_provisions'))->merge($get('banner_provisions'));

                                            $provisions = Provision::find($provisionIds);
                                            $provisionPrices = $provisions->pluck('product.price.price', 'id')->sum();
                                            $provisionTaxes = $provisions->pluck('product.price.tax_amount', 'id')->sum();
                                            $provisionCost = $provisions->pluck('product.price.cost', 'id')->sum();

                                            $total = $provisionPrices + ((float) $get('donnation_provision_amount'));
                                            $totalTaxes = $provisionTaxes;
                                            $totalNet = $provisionCost + ((float) $get('donnation_provision_amount'));

                                            return new HtmlString(
                                                '<table>'.
                                                '<tr><td>Net</td><td>'.PricingService::format($totalNet).'</td></tr>'.
                                                '<tr><td>TVA</td><td>'.($totalTaxes > 0 ? PricingService::format($totalTaxes) : '-').'</td></tr>'.
                                                '<tr><td style="width: 50px;">Total</td><td>'.PricingService::format($total).'</td></tr>'.
                                                '</table>');
                                        }),
                                    Textarea::make('note')
                                            ->label('Note général que vous aimeriez communiquer'),

                                    Placeholder::make('journal_indications')
                                        ->label('')
                                        ->visible(fn (Get $get) => $get('journal_provisions'))
                                        ->content(new HtmlString(Blade::render(<<<BLADE
                                            <div class="format">
                                                <h2>Annonces journalistique</h2>
                                                <p>Transmettre votre visuel au format numérique à <a href="mailto:pub@coursedenoel.ch">pub@coursedenoel.ch</a></p>
                                                <ul>
                                                    <li>Délai : 9 octobre 2024</li>
                                                    <li>Dimensions : selon les dimensions sélectionnées en mm</li>
                                                    <li>Format : PDF/X ou image très haute résolution</li>
                                                </ul>
                                            </div>
                                        BLADE))),
                                    Placeholder::make('screen_indications')
                                        ->label('')
                                        ->visible(fn (Get $get) => $get('screen_provisions'))
                                        ->content(new HtmlString(Blade::render(<<<BLADE
                                            <div class="format">
                                                <h2>Écran dans la tente principale</h2>
                                                <p>Transmettre votre visuel au format numérique à <a href="mailto:pub@coursedenoel.ch">pub@coursedenoel.ch</a></p>
                                                <ul>
                                                    <li>Délai : 17 novembre 2024</li>
                                                    <li>Contenu : Votre logo ou un visuel spécifique</li>
                                                    <li>Dimensions : 1920x1080</li>
                                                    <li>Définition : 300 dpi</li>
                                                    <li>Format : JPEG, JPG, TIFF ou PNG</li>
                                                </ul>
                                            </div>
                                        BLADE))),
                                    Placeholder::make('banner_indications')
                                        ->label('')
                                        ->visible(fn (Get $get) => $get('banner_provisions'))
                                        ->content(new HtmlString(Blade::render(<<<BLADE
                                            <div class="format">
                                                <h2>Banderoles</h2>
                                                <p>Les banderoles seront collectées le jeudi N décembre au plus tard.</a></p>
                                            </div>
                                        BLADE))),
                            ]),
                    ])
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
                        <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            Envoyer
                        </x-filament::button>
                    BLADE))),
            ])
            ->statePath('data')
            ->model(Client::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $dataObject = json_decode(json_encode($data));

        // Client
        $client = Client::create([
            'name' => $dataObject->name,
            'address' => $dataObject->address,
            'postal_code' => $dataObject->postal_code,
            'locality' => $dataObject->locality,
            'category_id' => setting('advertiser_form_client_category'),
            'invoicing_email' => $dataObject->invoicing_email,
            'invoicing_address' => $dataObject->invoicing_address,
            'invoicing_address_extension' => $dataObject->invoicing_address_extension,
            'invoicing_postal_code' => $dataObject->invoicing_postal_code,
            'invoicing_locality' => $dataObject->invoicing_locality,
            'note' => $dataObject->note,
        ]);

        // Contact
        if ($dataObject->contact->email) {
            $contact = Contact::create([
                'first_name' => $dataObject->contact->first_name,
                'last_name' => $dataObject->contact->last_name,
                'email' => $dataObject->contact->email,
            ]);
            $client->contacts()->attach($contact->id);
        }

        // Journal
        foreach ($dataObject->journal_provisions as $journalProvision) {
            $provision = Provision::find($journalProvision);
            $journalProvisionElement = ProvisionElement::create([
                'recipient_id' => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id' => $journalProvision,
                'status' => 'to_prepare',
                'has_product' => true,
                'quantity' => 1,
                'cost' => $provision->product?->cost,
                'tax_rate' => $provision->product?->tax_rate,
                'include_vat' => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($journalProvisionElement);
        }

        // Banner
        foreach ($dataObject->banner_provisions as $bannerProvision) {
            $provision = Provision::find($bannerProvision);
            $bannerProvisionElement = ProvisionElement::create([
                'recipient_id' => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id' => $bannerProvision,
                'status' => 'to_prepare',
                'has_product' => true,
                'quantity' => 1,
                'cost' => $provision->product?->cost,
                'tax_rate' => $provision->product?->tax_rate,
                'include_vat' => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($bannerProvisionElement);
        }

        // Screen
        foreach ($dataObject->screen_provisions as $screenProvision) {
            $provision = Provision::find($screenProvision);
            $screenProvisionElement = ProvisionElement::create([
                'recipient_id' => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id' => $screenProvision,
                'status' => 'to_prepare',
                'has_product' => true,
                'quantity' => 1,
                'cost' => $provision->product?->cost,
                'tax_rate' => $provision->product?->tax_rate,
                'include_vat' => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($screenProvisionElement);
        }

        // Donation
        if ($dataObject->donnation_provision_amount) {
            $provision = Provision::find(setting('advertiser_form_donation_provision'));
            $donationProvisionElement = ProvisionElement::create([
                'recipient_id' => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id' => $provision->id,
                'status' => 'to_prepare',
                'has_product' => true,
                'quantity' => 1,
                'cost' => $dataObject->donnation_provision_amount,
                'tax_rate' => $provision->product?->tax_rate ?? null,
                'include_vat' => $provision->product?->include_vat ?? true,
                'textual_indicator' => $dataObject->donnation_provision_mention,
            ]);
            $client->provisionElements()->save($donationProvisionElement);
        }

        dd('redirect');
    }

    public function render(): View
    {
        return view('livewire.advertiser-form');
    }
}
