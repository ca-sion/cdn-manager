<?php

namespace App\Livewire;

use App\Models\Run;
use Livewire\Component;
use Filament\Schemas\Schema;
use App\Helpers\CountryHelper;
use App\Models\RunRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use App\Notifications\EliteRunnerFormLink;
use App\Notifications\RunRegistrationLink;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Notifications\EliteRunnerContractFinalized;
use Filament\Actions\Concerns\InteractsWithActions;

class FrontEliteRegistration extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?RunRegistration $registration = null;

    public ?array $data = [];

    public bool $isManager = false;

    public function mount($registration = null): void
    {
        $this->isManager = session()->has('site_protected_authenticated') || auth()->check();

        if (! $this->isManager && (! $registration || (is_object($registration) && ! $registration->exists))) {
            abort(404);
        }

        if ($registration) {
            $this->registration = is_numeric($registration) || is_string($registration)
                ? RunRegistration::findOrFail($registration)
                : $registration;
            $registrationData = $registration->toArray();

            $firstRunner = $this->registration->runRegistrationElements()->withTrashed()->first();
            if ($firstRunner) {
                $registrationData['elite_first_name'] = $firstRunner->first_name;
                $registrationData['elite_last_name'] = $firstRunner->last_name;
                $registrationData['elite_birthdate'] = $firstRunner->birthdate?->format('Y-m-d');
                $registrationData['elite_gender'] = is_object($firstRunner->gender) ? $firstRunner->gender->value : $firstRunner->gender;
                $registrationData['elite_nationality'] = $firstRunner->nationality ?? 'SUI';
                $registrationData['elite_team'] = $firstRunner->team;
                $registrationData['elite_email'] = $firstRunner->email;
                $registrationData['elite_address'] = $firstRunner->address;
                $registrationData['elite_address_extension'] = $firstRunner->address_extension;
                $registrationData['elite_postal_code'] = $firstRunner->postal_code;
                $registrationData['elite_locality'] = $firstRunner->locality;
                $registrationData['elite_country'] = $firstRunner->country ?? 'SUI';
                $registrationData['payment_iban'] = $firstRunner->iban ?: $registration->payment_iban;
                $registrationData['has_free_registration_fee'] = $firstRunner->has_free_registration_fee;
                $registrationData['has_bonus_start'] = $firstRunner->has_bonus_start;
                $registrationData['bonus_start_amount'] = $firstRunner->bonus_start_amount;
                $registrationData['bonus_ranking_amount'] = $firstRunner->bonus_ranking_amount;
                $registrationData['bonus_arrival_amount'] = $firstRunner->bonus_arrival_amount;
                $registrationData['has_accommodation'] = $firstRunner->has_accommodation;
                $registrationData['accommodation_friday'] = $firstRunner->accommodation_friday;
                $registrationData['accommodation_saturday'] = $firstRunner->accommodation_saturday;
                $registrationData['accommodation_precision'] = $firstRunner->accommodation_precision;
                $registrationData['has_expense_reimbursement'] = $firstRunner->has_expense_reimbursement;
                $registrationData['expense_reimbursement_precision'] = $firstRunner->expense_reimbursement_precision;
            }

            $this->form->fill($registrationData);
        } else {
            $this->form->fill([
                'elite_nationality' => 'SUI',
                'elite_country'     => 'SUI',
                'elite_gender'      => 'M',
            ]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité du coureur')
                    ->icon('heroicon-m-trophy')
                    ->columns(2)
                    ->schema([
                        TextInput::make('elite_first_name')
                            ->label('Prénom')
                            ->required(),

                        TextInput::make('elite_last_name')
                            ->label('Nom de famille')
                            ->required(),

                        DatePicker::make('elite_birthdate')
                            ->label('Date de naissance')
                            ->displayFormat('d.m.Y')
                            ->required(),

                        Select::make('elite_gender')
                            ->label('Genre')
                            ->options(['M' => 'Masculin (M)', 'F' => 'Féminin (F)'])
                            ->required(),

                        Select::make('elite_nationality')
                            ->label('Nationalité')
                            ->options(CountryHelper::getOptions())
                            ->searchable()
                            ->default('SUI')
                            ->required(),

                        TextInput::make('elite_team')
                            ->label('Équipe / Club')
                            ->required(fn () => ! $this->isManager),

                        TextInput::make('elite_email')
                            ->label('Adresse email du coureur')
                            ->required(fn () => ! $this->isManager)
                            ->email(),
                    ]),

                Section::make('Coordonnées')
                    ->icon('heroicon-m-home')
                    ->columns(12)
                    ->schema([
                        TextInput::make('elite_address')
                            ->label('Adresse')
                            ->columnSpan(4),

                        TextInput::make('elite_address_extension')
                            ->label('Complément d\'adresse')
                            ->columnSpan(3),

                        TextInput::make('elite_postal_code')
                            ->label('Code postal')
                            ->columnSpan(2),

                        TextInput::make('elite_locality')
                            ->label('Localité')
                            ->columnSpan(3),

                        Select::make('elite_country')
                            ->label('Pays')
                            ->options(CountryHelper::getOptions())
                            ->searchable()
                            ->default('SUI')
                            ->columnSpan(3),

                        TextInput::make('payment_iban')
                            ->label('IBAN')
                            ->hint('Pour le versement des primes')
                            ->required(fn () => ! $this->isManager)
                            ->columnSpan(9),
                    ]),

                Section::make('Conditions et contrat')
                    ->icon('heroicon-m-document-text')
                    ->visible(fn () => $this->isManager)
                    ->columns(2)
                    ->schema([

                        Toggle::make('has_free_registration_fee')
                            ->label('Dossard offert')
                            ->columnSpanFull()
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        Toggle::make('has_bonus_start')
                            ->label('Prime de départ accordée')
                            ->live()
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        TextInput::make('bonus_start_amount')
                            ->label('Montant prime de départ')
                            ->numeric()
                            ->suffix('CHF')
                            ->visible(fn (Get $get) => $get('has_bonus_start'))
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        Toggle::make('has_expense_reimbursement')
                            ->label('Remboursement des frais de déplacement')
                            ->live()
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated()
                            ->columnSpanFull(),

                        Textarea::make('expense_reimbursement_precision')
                            ->label('Précisions remboursement de frais')
                            ->visible(fn (Get $get) => $get('has_expense_reimbursement'))
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                Section::make('Primes')
                    ->icon('heroicon-m-currency-dollar')
                    ->visible(fn () => $this->isManager)
                    ->columns(2)
                    ->schema([

                        TextInput::make('bonus_ranking_amount')
                            ->label('Montant prime de classement')
                            ->live()
                            ->numeric()
                            ->suffix('CHF')
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        TextInput::make('bonus_arrival_amount')
                            ->label('Montant prime d\'arrivée')
                            ->numeric()
                            ->suffix('CHF')
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),
                    ]),

                Section::make('Hébergement')
                    ->icon('heroicon-m-building-office')
                    ->visible(fn () => $this->isManager)
                    ->columns(2)
                    ->schema([

                        Toggle::make('has_accommodation')
                            ->label('Prise en charge de l\'hébergement')
                            ->live()
                            ->columnSpanFull()
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        Toggle::make('accommodation_friday')
                            ->label('Nuitée du vendredi')
                            ->visible(fn (Get $get) => $get('has_accommodation'))
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        Toggle::make('accommodation_saturday')
                            ->label('Nuitée du samedi')
                            ->visible(fn (Get $get) => $get('has_accommodation'))
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated(),

                        Textarea::make('accommodation_precision')
                            ->label('Précisions hébergement')
                            ->hint('Type de chambre et nombre de place.')
                            ->visible(fn (Get $get) => $get('has_accommodation'))
                            ->disabled(fn () => ! $this->isManager)
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $formData = $this->form->getState();
        $isNew = ! $this->registration || ! $this->registration->exists;

        if ($isNew) {
            $this->registration = new RunRegistration;
        }

        $firstRunner = $this->registration->exists
            ? $this->registration->runRegistrationElements()->withTrashed()->first()
            : null;

        $firstName = ! empty($formData['elite_first_name']) ? $formData['elite_first_name'] : ($firstRunner?->first_name ?? $this->registration->contact_first_name ?? '');
        $lastName = ! empty($formData['elite_last_name']) ? $formData['elite_last_name'] : ($firstRunner?->last_name ?? $this->registration->contact_last_name ?? '');
        $email = ! empty($formData['elite_email']) ? $formData['elite_email'] : ($firstRunner?->email ?? $this->registration->contact_email ?? '');

        $this->registration->fill([
            'run_registration_type' => 'elite',
            'contact_first_name'    => $firstName,
            'contact_last_name'     => $lastName,
            'contact_email'         => $email,
        ]);

        $this->registration->save();

        // Single Elite race
        $eliteRun = Run::where('name', 'LIKE', '%Elite%')->first();

        $runnerData = [
            'first_name'                      => $firstName,
            'last_name'                       => $lastName,
            'birthdate'                       => $formData['elite_birthdate'] ?? $firstRunner?->birthdate,
            'gender'                          => $formData['elite_gender'] ?? $firstRunner?->gender ?? 'M',
            'nationality'                     => $formData['elite_nationality'] ?? $firstRunner?->nationality ?? 'SUI',
            'team'                            => $formData['elite_team'] ?? $firstRunner?->team,
            'email'                           => $email,
            'run_id'                          => $eliteRun?->id,
            'run_name'                        => $eliteRun?->name ?? 'Course Élite',
            'address'                         => $formData['elite_address'] ?? $firstRunner?->address,
            'address_extension'               => $formData['elite_address_extension'] ?? $firstRunner?->address_extension,
            'postal_code'                     => $formData['elite_postal_code'] ?? $firstRunner?->postal_code,
            'locality'                        => $formData['elite_locality'] ?? $firstRunner?->locality,
            'country'                         => $formData['elite_country'] ?? $firstRunner?->country ?? 'SUI',
            'iban'                            => $formData['payment_iban'] ?? $firstRunner?->iban,
            'has_free_registration_fee'       => $formData['has_free_registration_fee'] ?? $firstRunner?->has_free_registration_fee ?? false,
            'has_bonus_start'                 => $formData['has_bonus_start'] ?? $firstRunner?->has_bonus_start ?? false,
            'bonus_start_amount'              => $formData['bonus_start_amount'] ?? $firstRunner?->bonus_start_amount,
            'bonus_ranking_amount'            => $formData['bonus_ranking_amount'] ?? $firstRunner?->bonus_ranking_amount,
            'bonus_arrival_amount'            => $formData['bonus_arrival_amount'] ?? $firstRunner?->bonus_arrival_amount,
            'has_accommodation'               => $formData['has_accommodation'] ?? $firstRunner?->has_accommodation ?? false,
            'accommodation_friday'            => $formData['accommodation_friday'] ?? $firstRunner?->accommodation_friday ?? false,
            'accommodation_saturday'          => $formData['accommodation_saturday'] ?? $firstRunner?->accommodation_saturday ?? false,
            'accommodation_precision'         => $formData['accommodation_precision'] ?? $firstRunner?->accommodation_precision,
            'has_expense_reimbursement'       => $formData['has_expense_reimbursement'] ?? $firstRunner?->has_expense_reimbursement ?? false,
            'expense_reimbursement_precision' => $formData['expense_reimbursement_precision'] ?? $firstRunner?->expense_reimbursement_precision,
        ];

        $firstRunner = $this->registration->runRegistrationElements()->withTrashed()->first();
        if ($firstRunner) {
            if ($firstRunner->trashed()) {
                $firstRunner->restore();
            }
            $firstRunner->update($runnerData);
        } else {
            $this->registration->runRegistrationElements()->create($runnerData);
        }

        if ($isNew) {
            try {
                $this->registration->notify(new RunRegistrationLink);
            } catch (\Throwable $e) {
                // Ignore
            }

            return redirect()->to(URL::signedRoute('front.run-registration.edit', [
                'registration' => $this->registration->id,
            ]))->with('message', 'Votre dossier d\'inscription a été créé.');
        }

        session()->flash('message', 'Dossier d\'inscription enregistré avec succès.');
    }

    public function getPdfUrlProperty(): ?string
    {
        if (! $this->registration || ! $this->registration->exists) {
            return null;
        }

        return URL::signedRoute('pdf.elite-contract', [
            'registration' => $this->registration->id,
        ]);
    }

    public function sendEmailLink(): void
    {
        if (! $this->registration || ! $this->registration->exists) {
            return;
        }

        if (! empty($this->data['elite_email']) && $this->data['elite_email'] !== $this->registration->contact_email) {
            $this->registration->contact_email = $this->data['elite_email'];
            $this->registration->save();
        }

        $element = $this->registration->runRegistrationElements()->first();
        if (! $element) {
            return;
        }

        try {
            $this->registration->notify(new EliteRunnerFormLink($element));
            $recipient = $this->registration->contact_email ?: ($this->data['elite_email'] ?? 'le coureur');
            session()->flash('message', 'Le lien vers la fiche a été envoyé par e-mail à '.$recipient.'.');
        } catch (\Throwable $e) {
            session()->flash('message', 'Erreur lors de l\'envoi de l\'e-mail : '.$e->getMessage());
        }
    }

    public function sendContractEmail(): void
    {
        if (! $this->registration || ! $this->registration->exists) {
            return;
        }

        $element = $this->registration->runRegistrationElements()->first();
        if (! $element) {
            return;
        }

        try {
            $this->registration->notify(new EliteRunnerContractFinalized($element));
            $recipient = $this->registration->contact_email ?: ($this->data['elite_email'] ?? 'le coureur');
            session()->flash('message', 'Le contrat PDF finalisé a été envoyé par e-mail à '.$recipient.'.');
        } catch (\Throwable $e) {
            session()->flash('message', 'Erreur lors de l\'envoi de l\'e-mail : '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $element = $this->registration?->runRegistrationElements()->first();

        return view('livewire.front-elite-registration', [
            'element' => $element,
            'pdfUrl'  => $this->pdfUrl,
        ]);
    }
}
