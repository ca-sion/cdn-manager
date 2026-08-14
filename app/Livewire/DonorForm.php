<?php

namespace App\Livewire;

use App\Classes\Price;
use App\Models\Contact;
use Livewire\Component;
use App\Models\Provision;
use Filament\Schemas\Schema;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Wizard\Step;
use App\Notifications\ContactDonorFormCreated;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;

class DonorForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Formulaire')
                        ->icon('heroicon-m-heart')
                        ->columns(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->label('Prénom')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', trim(($get('first_name') ?? '').' '.($get('last_name') ?? '').' - '.($get('role') ?? ''), ' -'));
                                })
                                ->afterStateHydrated(function (Get $get, Set $set) {
                                    $set('donnation_provision_mention', trim(($get('first_name') ?? '').' '.($get('last_name') ?? '').' - '.($get('role') ?? ''), ' -'));
                                })
                                ->disabled(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->dehydrated(),
                            TextInput::make('last_name')
                                ->label('Nom de famille')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', trim(($get('first_name') ?? '').' '.($get('last_name') ?? '').' - '.($get('role') ?? ''), ' -'));
                                })
                                ->disabled(fn (DonorForm $livewire) => $livewire->contact?->first_name)
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->last_name)
                                ->dehydrated(),
                            TextInput::make('role')
                                ->label('Fonction/Titre')
                                ->prefixIcon('heroicon-m-briefcase')
                                ->live(debounce: 500)
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                                    $set('donnation_provision_mention', trim(($get('first_name') ?? '').' '.($get('last_name') ?? '').' - '.($get('role') ?? ''), ' -'));
                                })
                                ->readOnly(fn (DonorForm $livewire) => $livewire->contact?->role),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->prefixIcon('heroicon-m-envelope')
                                ->required(),
                            Section::make('Don d\'honneur')
                                ->icon('heroicon-m-sparkles')
                                ->description('Crédité le jour de la course')
                                ->columnSpanFull()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('donnation_provision_amount')
                                        ->label('Montant annoncé')
                                        ->helperText('Le montant n\'est pas soumis à la TVA')
                                        ->numeric()
                                        ->suffix('CHF')
                                        ->prefixIcon('heroicon-m-banknotes')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('donnation_provision_mention')
                                        ->label('Mention à côté du montant')
                                        ->helperText('Mentionner si anonyme')
                                        ->prefixIcon('heroicon-m-chat-bubble-bottom-center-text')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(),
                                ]),
                        ]),
                    Step::make('Récapitulatif')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([
                            Section::make('Détails de la commande')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('donor_name')
                                        ->label('Donateur')
                                        ->state(fn (Get $get) => trim(($get('first_name') ?? '').' '.($get('last_name') ?? '')) ?: '-')
                                        ->icon('heroicon-m-user')
                                        ->weight(FontWeight::Bold),
                                    TextEntry::make('donor_email')
                                        ->label('Email')
                                        ->state(fn (Get $get) => $get('email') ?: '-')
                                        ->icon('heroicon-m-envelope'),
                                    TextEntry::make('donor_role')
                                        ->label('Fonction/Titre')
                                        ->state(fn (Get $get) => $get('role') ?: '-')
                                        ->icon('heroicon-m-briefcase')
                                        ->visible(fn (Get $get) => ! empty($get('role'))),
                                    TextEntry::make('summary_donation_amount')
                                        ->label('Montant du don')
                                        ->state(fn (Get $get) => $get('donnation_provision_amount') ? Price::of((float) $get('donnation_provision_amount'))->amount('c') : '-')
                                        ->icon('heroicon-m-banknotes')
                                        ->badge()
                                        ->color('success')
                                        ->size(TextSize::Large),
                                    TextEntry::make('summary_donation_mention')
                                        ->label('Mention du don')
                                        ->state(fn (Get $get) => $get('donnation_provision_mention') ?: '-')
                                        ->icon('heroicon-m-chat-bubble-bottom-center-text'),
                                ]),
                            Textarea::make('note')
                                ->label('Remarque ou ajout que vous aimeriez communiquer')
                                ->rows(3)
                                ->columnSpanFull(),
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
