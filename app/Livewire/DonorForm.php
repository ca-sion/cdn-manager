<?php

namespace App\Livewire;

use App\Classes\Price;
use App\Models\Contact;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
use App\Notifications\ContactDonorFormCreated;
use Filament\Forms\Concerns\InteractsWithForms;

class DonorForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?Contact $contact = null;

    public function mount(): void
    {
        $contact = request()->route()->parameter('contact');

        if ($contact && request()->hasValidSignature()) {
            $this->contact = $contact;
            if ($this->contact) {
                $this->form->fill([
                    'first_name' => $this->contact->first_name,
                    'last_name'  => $this->contact->last_name,
                    'email'      => $this->contact->email,
                    'role'       => $this->contact->role,
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
                    Wizard\Step::make('Formulaire')
                        ->columns(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->label('Prénom')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', $get('first_name').' '.$get('last_name').' - '.$get('role'));
                                })
                                ->afterStateHydrated(function (Get $get, Set $set) {
                                    $set('donnation_provision_mention', $get('first_name').' '.$get('last_name').' - '.$get('role'));
                                })
                                ->disabled(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->dehydrated(),
                            TextInput::make('last_name')
                                ->label('Nom de famille')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', $get('first_name').' '.$get('last_name').' - '.$get('role'));
                                })
                                ->disabled(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->last_name)
                                ->dehydrated(),
                            TextInput::make('role')
                                ->label('Fonction/Titre')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', $get('first_name').' '.$get('last_name').' - '.$get('role'));
                                })
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->role),
                            TextInput::make('email')
                                ->label('Email')
                                ->required(),
                            Section::make('Don d\'honneur')
                                ->description('Crédité dans l\'encarté du Nouvelliste')
                                ->columnSpanFull()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('donnation_provision_amount')
                                        ->label('Montant annoncé')
                                        ->helperText('Le montant n\'est pas soumis à la TVA')
                                        ->numeric()
                                        ->suffix('CHF')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('donnation_provision_mention')
                                        ->label('Mention dans l\'encarté à côté du montant')
                                        ->helperText('Mentionner si anynyme')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(),
                                ]),
                        ]),
                    Wizard\Step::make('Récapitulatif')
                        ->schema([
                            Placeholder::make('details')
                                ->label(new HtmlString('<div class="format"><h2>Détails de la commande</h2></div>'))
                                ->content(function (Get $get, Component $livewire) {

                                    $donnation_provision_amount = (float) $get('donnation_provision_amount');
                                    $donnation_provision_mention = $get('donnation_provision_mention');

                                    $totalNet = $donnation_provision_amount;

                                    return view('livewire.advertiser-form-order-details', [
                                        'total_net'                 => Price::of($totalNet)->amount('c'),
                                        'total_taxes'               => '-',
                                        'total'                     => Price::of($totalNet)->amount('c'),
                                        'data'                      => json_decode(json_encode($livewire->data)),
                                        'provisions'                => [],
                                        'donnationProvisionAmount'  => $donnation_provision_amount ? Price::of($donnation_provision_amount)->amount('c') : null,
                                        'donnationProvisionMention' => $donnation_provision_mention,
                                    ]);
                                }),
                            Textarea::make('note')
                                ->label('Remarque ou ajout que vous aimeriez communiquer'),
                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            Envoyer et recevoir les instructions
                        </x-filament::button>
                    BLADE))),
            ])
            ->statePath('data')
            ->model(Contact::class);
    }

    public function create()
    {
        $data = $this->form->getState();
        $dataObject = json_decode(json_encode($data));

        // Contact
        if ($this->contact) {
            $contact = $this->contact;
            $contact->update([
                'first_name' => $dataObject->first_name,
                'last_name'  => $dataObject->last_name,
                'email'      => $dataObject->email,
            ]);
        } else {
            $contact = Contact::create([
                'first_name' => $dataObject->first_name,
                'last_name'  => $dataObject->last_name,
                'email'      => $dataObject->email,
            ]);
        }

        // Donation
        if ($dataObject->donnation_provision_amount) {
            $provision = Provision::find(setting('advertiser_form_donation_provision'));
            $donationProvisionElement = ProvisionElement::create([
                'recipient_id'      => $contact->id,
                'recipient_type'    => 'App\Models\Contact',
                'provision_id'      => $provision->id,
                'status'            => 'to_prepare',
                'has_product'       => true,
                'quantity'          => 1,
                'cost'              => $dataObject->donnation_provision_amount,
                'tax_rate'          => $provision->product?->tax_rate ?? null,
                'include_vat'       => $provision->product?->include_vat ?? true,
                'textual_indicator' => $dataObject->donnation_provision_mention,
                'note'              => $dataObject->note,
            ]);
            $contact->provisionElements()->save($donationProvisionElement);
        }

        // Email
        $contact->notify(new ContactDonorFormCreated($donationProvisionElement));

        // Redirect
        return redirect()->to(URL::signedRoute('donors.success', ['contact' => $contact, 'dpe' => $donationProvisionElement]));
    }

    public function render(): View
    {
        return view('livewire.donor-form');
    }
}
