<?php

namespace App\Livewire;

use App\Classes\Price;
use App\Models\Client;
use App\Models\Contact;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Provision;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Notifications\ClientAdvertiserFormCreated;

class AdvertiserForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?Client $client = null;

    public function mount(): void
    {
        $client = request()->route()->parameter('client');

        if ($client && request()->hasValidSignature()) {
            $this->client = $client;
            if ($this->client) {
                $this->form->fill([
                    'name'                        => $this->client->name,
                    'address'                     => $this->client->address,
                    'postal_code'                 => $this->client->postal_code,
                    'locality'                    => $this->client->locality,
                    'invoicing_email'             => $this->client->invoicing_email,
                    'invoicing_address'           => $this->client->invoicing_address,
                    'invoicing_address_extension' => $this->client->invoicing_address_extension,
                    'invoicing_postal_code'       => $this->client->invoicing_postal_code,
                    'invoicing_locality'          => $this->client->invoicing_locality,
                    'note'                        => $this->client->note,
                    'contact'                     => [
                        'first_name' => $this->client->contacts->first()->first_name ?? null,
                        'last_name'  => $this->client->contacts->first()->last_name ?? null,
                        'email'      => $this->client->contacts->first()->email ?? null,
                    ],
                ]);
            } else {
                $this->form->fill();
            }
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Informations')
                        ->visible(fn (AdvertiserForm $livewire) => $livewire->client !== null)
                        ->schema([
                            Placeholder::make('client_name')
                                ->label(new HtmlString('<strong>Nom</strong>'))
                                ->content(fn (AdvertiserForm $livewire) => $livewire->client?->name),
                            Placeholder::make('previous_order_details')
                                ->label(new HtmlString('<strong>Commande de l\'édition précédente</strong>'))
                                ->content(function (AdvertiserForm $livewire) {
                                    if (! $livewire->client) {
                                        return 'Aucune information disponible.';
                                    }

                                    if (empty($livewire->client->getPreviousEditionProvisionElementsDetails())) {
                                        return 'Aucune commande passée l\'année dernière.';
                                    }

                                    return new HtmlString('<ul style="list-style: disc;margin-left: 2rem;}"><li>'.implode('</li><li>', $livewire->client->getPreviousEditionProvisionElementsDetails()).'</li></ul>');
                                }),
                        ]),
                    Wizard\Step::make('Prestations')
                        ->schema([
                            Placeholder::make('Annonce journalistique')
                                ->label('')
                                ->content(new HtmlString('Sélectionner les prestations qui vous conviennent dans les listes ci-après.')),
                            Section::make('Anonce journalistique')
                                ->description(new HtmlString('Annonce dans le Journal de la Course de Noël et du Trail des Châteaux, édité à plus de 40 000 exemplaires, à paraître dans un Nouvelliste de novembre et distribué dans les districts de Sion, d’Hérens et de Conthey.<br><br>Consulter <a href="/docs/Dimensions_encarté_NF.pdf" class="underline text-primary-600 hover:text-primary-500">mise en page des emplacements</a> pour choisir votre emplacement et les dimensions de votre annonce.'))
                                ->schema([
                                    CheckboxList::make('journal_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_journal_category'))->orderBy('order_column')->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::where('category_id', setting('advertiser_form_journal_category'))->orderBy('order_column')->get()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->netAmount() ? $item->product?->price->netAmount('c') : null]))
                                        ->columns(2)
                                        ->live(),
                                ]),
                            Section::make('Banderoles')
                                ->schema([
                                    CheckboxList::make('banner_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_banner_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::all()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->netAmount() ? $item->product?->price->netAmount('c') : null]))
                                        ->live(),
                                ]),
                            Section::make('Écran dans la tente principale')
                                ->schema([
                                    CheckboxList::make('screen_provisions')
                                        ->label('')
                                        ->hint('Prix hors TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_screen_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::all()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->netAmount('c')]))
                                        ->live(),
                                ]),
                            Section::make('Packs')
                                ->schema([
                                    CheckboxList::make('pack_provisions')
                                        ->label('')
                                        ->hint('Prix avec TVA')
                                        ->options(Provision::where('category_id', setting('advertiser_form_pack_category'))->get()->mapWithKeys(fn ($item) => [$item->id => $item->description ?? $item->name]))
                                        ->descriptions(Provision::all()->mapWithKeys(fn ($item) => [$item->id => $item->product?->price->amount('c')]))
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
                                        ->rules(['email:rfc,dns'])
                                        ->live(),
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
                            Textarea::make('note')
                                ->label('Remarque ou ajout que vous aimeriez communiquer'),
                            Placeholder::make('details')
                                ->label(new HtmlString('<div class="format"><h2>Détails de la commande</h2></div>'))
                                ->content(function (Get $get, Component $livewire) {
                                    $provisionIds = collect($get('journal_provisions'))
                                        ->merge($get('screen_provisions'))
                                        ->merge($get('banner_provisions'))
                                        ->merge($get('pack_provisions'));

                                    $provisions = Provision::find($provisionIds);
                                    $subProvisions = $provisions->load('subProvisions')->pluck('subProvisions')->collapse();
                                    $provisionPrices = $provisions->pluck('product.price.amount', 'id')->sum();
                                    $provisionTaxes = $provisions->pluck('product.price.tax_amount', 'id')->sum();
                                    $provisionCost = $provisions->pluck('product.price.cost', 'id')->sum();

                                    $donnation_provision_amount = (float) $get('donnation_provision_amount');
                                    $donnation_provision_mention = $get('donnation_provision_mention');

                                    $total = $provisionPrices + $donnation_provision_amount;
                                    $totalTaxes = $provisionTaxes;
                                    $totalNet = $provisionCost + $donnation_provision_amount;

                                    return view('livewire.advertiser-form-order-details', [
                                        'total_net'                 => Price::of($totalNet)->amount('c'),
                                        'total_taxes'               => $totalTaxes > 0 ? Price::of($totalTaxes)->amount('c') : '-',
                                        'total'                     => Price::of($total)->amount('c'),
                                        'data'                      => json_decode(json_encode($livewire->data)),
                                        'provisions'                => $provisions->merge($subProvisions),
                                        'donnationProvisionAmount'  => $donnation_provision_amount ? Price::of($donnation_provision_amount)->amount('c') : null,
                                        'donnationProvisionMention' => $donnation_provision_mention,
                                    ]);
                                }),
                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
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

    public function create()
    {
        $data = $this->form->getState();
        $dataObject = json_decode(json_encode($data));

        // Client
        if ($this->client) {
            $client = $this->client;
            $client->update([
                'name'                        => $dataObject->name,
                'email'                       => $dataObject->contact->email,
                'address'                     => $dataObject->address,
                'postal_code'                 => $dataObject->postal_code,
                'locality'                    => $dataObject->locality,
                'invoicing_email'             => $dataObject->invoicing_email,
                'invoicing_address'           => $dataObject->invoicing_address,
                'invoicing_address_extension' => $dataObject->invoicing_address_extension,
                'invoicing_postal_code'       => $dataObject->invoicing_postal_code,
                'invoicing_locality'          => $dataObject->invoicing_locality,
                'note'                        => $dataObject->note,
            ]);

            // Update existing contact or create new one if email changed
            if ($dataObject->contact->email) {
                $contact = $client->contacts()->where('email', $dataObject->contact->email)->first();
                if ($contact) {
                    $contact->update([
                        'first_name' => $dataObject->contact->first_name,
                        'last_name'  => $dataObject->contact->last_name,
                    ]);
                } else {
                    $contact = Contact::create([
                        'first_name' => $dataObject->contact->first_name,
                        'last_name'  => $dataObject->contact->last_name,
                        'email'      => $dataObject->contact->email,
                    ]);
                    $client->contacts()->attach($contact->id);
                }
            }
        } else {
            $client = Client::create([
                'name'                        => $dataObject->name,
                'email'                       => $dataObject->contact->email,
                'address'                     => $dataObject->address,
                'postal_code'                 => $dataObject->postal_code,
                'locality'                    => $dataObject->locality,
                'category_id'                 => setting('advertiser_form_client_category'),
                'invoicing_email'             => $dataObject->invoicing_email,
                'invoicing_address'           => $dataObject->invoicing_address,
                'invoicing_address_extension' => $dataObject->invoicing_address_extension,
                'invoicing_postal_code'       => $dataObject->invoicing_postal_code,
                'invoicing_locality'          => $dataObject->invoicing_locality,
                'note'                        => $dataObject->note,
            ]);

            // Contact
            if ($dataObject->contact->email) {
                $contact = Contact::create([
                    'first_name' => $dataObject->contact->first_name,
                    'last_name'  => $dataObject->contact->last_name,
                    'email'      => $dataObject->contact->email,
                ]);
                $client->contacts()->attach($contact->id);
            }
        }

        // Journal
        foreach ($dataObject->journal_provisions as $journalProvision) {
            $provision = Provision::find($journalProvision);
            $journalProvisionElement = ProvisionElement::create([
                'recipient_id'   => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id'   => $journalProvision,
                'status'         => 'to_prepare',
                'has_product'    => true,
                'quantity'       => 1,
                'cost'           => $provision->product?->cost,
                'tax_rate'       => $provision->product?->tax_rate,
                'include_vat'    => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($journalProvisionElement);
        }

        // Banner
        foreach ($dataObject->banner_provisions as $bannerProvision) {
            $provision = Provision::find($bannerProvision);
            $bannerProvisionElement = ProvisionElement::create([
                'recipient_id'   => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id'   => $bannerProvision,
                'status'         => 'to_prepare',
                'has_product'    => true,
                'quantity'       => 1,
                'cost'           => $provision->product?->cost,
                'tax_rate'       => $provision->product?->tax_rate,
                'include_vat'    => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($bannerProvisionElement);
        }

        // Screen
        foreach ($dataObject->screen_provisions as $screenProvision) {
            $provision = Provision::find($screenProvision);
            $screenProvisionElement = ProvisionElement::create([
                'recipient_id'   => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id'   => $screenProvision,
                'status'         => 'to_prepare',
                'has_product'    => true,
                'quantity'       => 1,
                'cost'           => $provision->product?->cost,
                'tax_rate'       => $provision->product?->tax_rate,
                'include_vat'    => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($screenProvisionElement);
        }

        // Pack
        foreach ($dataObject->pack_provisions as $packProvision) {
            $provision = Provision::find($packProvision);
            $packProvisionElement = ProvisionElement::create([
                'recipient_id'   => $client->id,
                'recipient_type' => 'App\Models\Client',
                'provision_id'   => $packProvision,
                'status'         => 'to_modify',
                'has_product'    => true,
                'quantity'       => 1,
                'cost'           => $provision->product?->cost,
                'tax_rate'       => $provision->product?->tax_rate,
                'include_vat'    => $provision->product?->include_vat ?? false,
            ]);
            $client->provisionElements()->save($packProvisionElement);

            // Subprovisions
            foreach ($provision->subProvisions as $subProvision) {
                $provision = Provision::find($subProvision);
                $subProvisionElement = ProvisionElement::create([
                    'recipient_id'   => $client->id,
                    'recipient_type' => 'App\Models\Client',
                    'provision_id'   => $subProvision->id,
                    'status'         => 'to_prepare',
                ]);
                $client->provisionElements()->save($subProvisionElement);
            }

        }

        // Donation
        if ($dataObject->donnation_provision_amount) {
            $provision = Provision::find(setting('advertiser_form_donation_provision'));
            $donationProvisionElement = ProvisionElement::create([
                'recipient_id'      => $client->id,
                'recipient_type'    => 'App\Models\Client',
                'provision_id'      => $provision->id,
                'status'            => 'to_prepare',
                'has_product'       => true,
                'quantity'          => 1,
                'cost'              => $dataObject->donnation_provision_amount,
                'tax_rate'          => $provision->product?->tax_rate ?? null,
                'include_vat'       => $provision->product?->include_vat ?? true,
                'textual_indicator' => $dataObject->donnation_provision_mention,
            ]);
            $client->provisionElements()->save($donationProvisionElement);
        }

        // Email
        $client->notify(new ClientAdvertiserFormCreated);

        // Redirect
        return redirect()->to(URL::signedRoute('advertisers.success', ['client' => $client]));
    }

    public function render(): View
    {
        return view('livewire.advertiser-form');
    }
}
