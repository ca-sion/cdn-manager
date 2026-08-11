<?php

namespace App\Livewire;

use App\Models\Run;
use Livewire\Component;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Notifications\RunRegistrationLink;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use LaraGrid\Grid as LaraGrid;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\Columns\{SerialColumn, TextColumn, DateColumn, SelectColumn, CheckboxColumn, DecimalColumn};
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Illuminate\Contracts\View\View;

class FrontRunRegistration extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use WithLaraGrid;

    public ?array $data = [];
    public string $type = 'company';
    public $registration = null;

    // Grid elements array for LaraGrid (schools, groups, companies)
    public array $elements = [];

    public function mount($type = null, RunRegistration $registration = null): void
    {
        if ($type instanceof RunRegistration) {
            $registration = $type;
            $type = null;
        } elseif (is_numeric($type) || (is_string($type) && ! in_array($type, ['company', 'school', 'group', 'elite']))) {
            $found = RunRegistration::find($type);
            if ($found) {
                $registration = $found;
                $type = null;
            }
        }

        if ($registration) {
            $this->registration = is_numeric($registration) || is_string($registration)
                ? RunRegistration::findOrFail($registration)
                : $registration;

            $registrationData = $this->registration->toArray();
            $registrationType = $this->registration->run_registration_type?->value ?? $this->registration->type;
            if ($registrationType) {
                $type = is_object($registrationType) ? $registrationType->value : (string) $registrationType;
            }

            // Populate single Elite runner fields if type is elite
            if ($type === 'elite') {
                $firstRunner = $this->registration->runRegistrationElements()->withTrashed()->first();
                if ($firstRunner) {
                    $registrationData['elite_first_name'] = $firstRunner->first_name;
                    $registrationData['elite_last_name'] = $firstRunner->last_name;
                    $registrationData['elite_birthdate'] = $firstRunner->birthdate?->format('Y-m-d');
                    $registrationData['elite_gender'] = is_object($firstRunner->gender) ? $firstRunner->gender->value : $firstRunner->gender;
                    $registrationData['elite_nationality'] = $firstRunner->nationality;
                    $registrationData['elite_email'] = $firstRunner->email;
                    $registrationData['elite_run_id'] = $firstRunner->run_id;
                    $registrationData['elite_bloc'] = $firstRunner->bloc;
                    $registrationData['elite_address'] = $firstRunner->address;
                    $registrationData['elite_address_extension'] = $firstRunner->address_extension;
                    $registrationData['elite_postal_code'] = $firstRunner->postal_code;
                    $registrationData['elite_locality'] = $firstRunner->locality;
                    $registrationData['elite_country'] = $firstRunner->country ?? 'SUI';
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
            }

            $this->form->fill($registrationData);
        } else {
            $initialData = [];

            if ($clientId = request()->query('client_id')) {
                $client = \App\Models\Client::find($clientId);
                if ($client) {
                    $initialData = [
                        'client_id'              => $client->id,
                        'company_name'           => $client->name,
                        'invoicing_company_name' => $client->name,
                        'invoicing_address'      => $client->address,
                        'invoicing_postal_code'  => $client->postal_code,
                        'invoicing_locality'     => $client->locality,
                        'invoicing_email'        => $client->invoicingContactEmail ?? $client->email,
                        'contact_first_name'     => $client->contacts()->first()?->first_name ?? '',
                        'contact_last_name'      => $client->contacts()->first()?->last_name ?? '',
                        'contact_email'          => $client->contactEmail ?? $client->email,
                        'contact_phone'          => $client->phone,
                    ];
                }
            }

            $this->form->fill($initialData);
        }

        $this->type = in_array($type, ['company', 'school', 'group', 'elite']) ? $type : 'company';

        if ($this->type !== 'elite') {
            $elementsList = $this->registration 
                ? $this->registration->runRegistrationElements()->withTrashed()->get() 
                : collect();

            if ($elementsList->isNotEmpty()) {
                $this->elements = $elementsList->map(function ($el) {
                    $arr = array_merge($this->emptyElement(), $el->toArray());
                    $arr['_k'] = 'el_' . $el->id;
                    if ($el->birthdate) {
                        $arr['birthdate'] = $el->birthdate->format('Y-m-d');
                    }
                    if ($el->gender) {
                        $arr['gender'] = is_object($el->gender) ? $el->gender->value : $el->gender;
                    }
                    $arr['run_id'] = $el->run_id ? (string) $el->run_id : '';
                    return $arr;
                })->toArray();
            } else {
                $this->elements = $this->gridMountRows('elements');
            }
        }
    }

    public function getEstimatedTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->elements as $row) {
            if (empty($row['first_name']) && empty($row['last_name'])) {
                continue;
            }
            if (! empty($row['run_id'])) {
                $run = Run::find($row['run_id']);
                if ($run) {
                    $cost = $run->provision?->product?->price?->amount ?? $run->cost;
                    $total += (float) $cost;
                }
            }
        }
        return $total;
    }

    public function form(Schema $schema): Schema
    {
        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', $this->type)
                ->orWhereNull('available_for_types');
        })->get();

        $runOptions = [];
        foreach ($runs as $r) {
            $cost = $r->provision?->product?->price?->amount ?? $r->cost;
            $runOptions[$r->id] = $r->name . ' (' . ($cost ? $cost . ' CHF' : 'Gratuit') . ')';
        }

        // Blocs de départ pour la course entreprise
        $companyRun = Run::where(function ($q) {
            $q->whereJsonContains('available_for_types', 'company')
              ->orWhereNull('available_for_types');
        })->first();

        $rawBlocs = $companyRun?->start_blocs ?? [];
        $companyBlocOptions = [];

        if (is_array($rawBlocs)) {
            foreach ($rawBlocs as $b) {
                if (is_array($b)) {
                    $label = ($b['label'] ?? '') . (! empty($b['time']) ? ' (' . $b['time'] . ')' : '');
                    $val = $b['label'] ?? $label;
                    if ($val) {
                        $companyBlocOptions[$val] = $label;
                    }
                } elseif (is_string($b) && trim($b) !== '') {
                    $companyBlocOptions[$b] = $b;
                }
            }
        }

        if (empty($companyBlocOptions)) {
            $companyBlocOptions = [
                'Bloc 1' => 'Bloc 1',
                'Bloc 2' => 'Bloc 2',
                'Bloc 3' => 'Bloc 3',
            ];
        }

        return $schema
            ->components([
                Section::make('Informations sur la course & Remplissage')
                    ->icon('heroicon-m-flag')
                    ->collapsible()
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type !== 'elite')
                    ->schema([
                        Placeholder::make('metrics')
                            ->label('')
                            ->content(function (FrontRunRegistration $livewire) use ($runs) {
                                $deadline = setting('registrations_deadline');

                                return view('livewire.front-run-registration-metrics', [
                                    'runs'     => $runs,
                                    'deadline' => $deadline ? Carbon::parse($deadline)->format('d.m.Y à H:i') : null,
                                    'isLocked' => $livewire->isGridLocked(),
                                ]);
                            }),
                    ]),

                // SECTION SCHOOL 1: Centre scolaire et degré
                Section::make('Centre scolaire et degré')
                    ->icon('heroicon-m-academic-cap')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                    ->columns(2)
                    ->schema([
                        TextInput::make('school_name')
                            ->label('Nom du centre scolaire')
                            ->placeholder('Ex: Centre scolaire Bon-Pasteur')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('school_class_level')
                            ->label('Degré scolaire')
                            ->options([
                                '1H' => '1H',
                                '2H' => '2H',
                                '3H' => '3H',
                                '4H' => '4H',
                                '5H' => '5H',
                                '6H' => '6H',
                                '7H' => '7H',
                                '8H' => '8H',
                                '9CO' => '9CO',
                                '10CO' => '10CO',
                                '11CO' => '11CO',
                            ])
                            ->required(),

                        TextInput::make('school_postal_code')
                            ->label('NPA')
                            ->placeholder('1950')
                            ->numeric()
                            ->required(),

                        TextInput::make('school_locality')
                            ->label('Localité')
                            ->placeholder('Sion')
                            ->required(),
                    ]),

                // SECTION SCHOOL 2: Titulaire de la classe
                Section::make('Titulaire de la classe')
                    ->icon('heroicon-m-user-group')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                    ->columns(2)
                    ->schema([
                        TextInput::make('school_class_holder_first_name')
                            ->label('Prénom du titulaire')
                            ->required(),

                        TextInput::make('school_class_holder_last_name')
                            ->label('Nom du titulaire')
                            ->required(),

                        TextInput::make('school_class_holder_email')
                            ->label('e-mail du titulaire')
                            ->email()
                            ->required(),

                        TextInput::make('school_class_holder_phone')
                            ->label('N° de téléphone portable')
                            ->tel()
                            ->placeholder('079 123 45 67'),
                    ]),

                // SECTION COMPANY: Entreprise
                Section::make('Coordonnées de l\'entreprise')
                    ->icon('heroicon-m-building-office')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'company')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nom de l\'entreprise')
                            ->required()
                            ->columnSpanFull(),

                        Select::make('company_bloc')
                            ->label('Bloc de départ souhaité pour l\'équipe')
                            ->options($companyBlocOptions)
                            ->searchable()
                            ->columnSpanFull(),
                    ]),

                // SECTION COMMON: Personne responsable le jour de la course
                Section::make('Personne responsable le jour de la course')
                    ->icon('heroicon-m-user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_first_name')
                            ->label('Prénom du responsable')
                            ->required(),

                        TextInput::make('contact_last_name')
                            ->label('Nom du responsable')
                            ->required(),

                        TextInput::make('contact_email')
                            ->label('e-mail du responsable')
                            ->email()
                            ->required(),

                        TextInput::make('contact_phone')
                            ->label('N° de téléphone portable')
                            ->tel()
                            ->placeholder('079 123 45 67'),
                    ]),

                // SECTION ELITE RUNNER FORM (1 Inscription = 1 Coureur Élite)
                Section::make('Identité & Inscription du Coureur Élite')
                    ->icon('heroicon-m-trophy')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite')
                    ->columns(2)
                    ->schema([
                        TextInput::make('elite_first_name')
                            ->label('Prénom du coureur')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite'),

                        TextInput::make('elite_last_name')
                            ->label('Nom du coureur')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite'),

                        DatePicker::make('elite_birthdate')
                            ->label('Date de naissance')
                            ->displayFormat('d.m.Y')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite'),

                        Select::make('elite_gender')
                            ->label('Genre')
                            ->options(['M' => 'Masculin (M)', 'F' => 'Féminin (F)'])
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite'),

                        TextInput::make('elite_nationality')
                            ->label('Nationalité')
                            ->default('SUI'),

                        TextInput::make('elite_email')
                            ->label('Adresse email du coureur')
                            ->email(),

                        Select::make('elite_run_id')
                            ->label('Course Élite')
                            ->options($runOptions)
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite'),

                        TextInput::make('elite_bloc')
                            ->label('Bloc de départ'),
                    ]),

                Section::make('Adresse du Coureur Élite')
                    ->icon('heroicon-m-home')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite')
                    ->columns(2)
                    ->schema([
                        TextInput::make('elite_address')
                            ->label('Adresse')
                            ->columnSpanFull(),

                        TextInput::make('elite_address_extension')
                            ->label('Complément d\'adresse'),

                        TextInput::make('elite_postal_code')
                            ->label('Code postal'),

                        TextInput::make('elite_locality')
                            ->label('Localité'),

                        TextInput::make('elite_country')
                            ->label('Pays')
                            ->default('SUI'),
                    ]),

                Section::make('Conditions Financières & Contrat Élite')
                    ->icon('heroicon-m-currency-dollar')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'elite')
                    ->columns(2)
                    ->schema([
                        TextInput::make('payment_iban')
                            ->label('IBAN de versement')
                            ->columnSpanFull(),

                        Checkbox::make('has_free_registration_fee')
                            ->label('Frais de dossier offerts'),

                        Checkbox::make('has_bonus_start')
                            ->label('Prime de départ accordée'),

                        TextInput::make('bonus_start_amount')
                            ->label('Montant prime de départ (CHF)')
                            ->numeric(),

                        TextInput::make('bonus_ranking_amount')
                            ->label('Montant prime de classement (CHF)')
                            ->numeric(),

                        TextInput::make('bonus_arrival_amount')
                            ->label('Montant prime d\'arrivée (CHF)')
                            ->numeric(),

                        Checkbox::make('has_accommodation')
                            ->label('Prise en charge hébergement'),

                        Checkbox::make('accommodation_friday')
                            ->label('Nuitée du vendredi'),

                        Checkbox::make('accommodation_saturday')
                            ->label('Nuitée du samedi'),

                        Textarea::make('accommodation_precision')
                            ->label('Précisions hébergement')
                            ->columnSpanFull(),

                        Checkbox::make('has_expense_reimbursement')
                            ->label('Remboursement de frais de déplacement'),

                        Textarea::make('expense_reimbursement_precision')
                            ->label('Précisions remboursement de frais')
                            ->columnSpanFull(),
                    ]),

                // SECTION FACTURATION (Entreprises & Groupes - pas pour Écoles/Élites)
                Section::make('Facturation & Règlement')
                    ->icon('heroicon-m-credit-card')
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type !== 'school' && $livewire->type !== 'elite')
                    ->schema([
                        TextInput::make('invoicing_company_name')
                            ->label('Raison sociale de facturation')
                            ->columnSpanFull(),

                        TextInput::make('invoicing_address')
                            ->label('Adresse de facturation'),

                        TextInput::make('invoicing_address_extension')
                            ->label('Complément d\'adresse'),

                        TextInput::make('invoicing_postal_code')
                            ->label('Code postal'),

                        TextInput::make('invoicing_locality')
                            ->label('Localité'),

                        TextInput::make('invoicing_email')
                            ->label('Email d\'envoi de la facture')
                            ->email()
                            ->columnSpanFull(),

                        Textarea::make('invoicing_note')
                            ->label('Remarque facturation')
                            ->columnSpanFull(),
                    ]),

                // SECTION PARTICIPANTS LARAGRID (Masquée pour Élite)
                Section::make('Participants à inscrire (Grille interactive)')
                    ->icon('heroicon-m-table-cells')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type !== 'elite')
                    ->description('Remplissez les informations de chaque coureur dans le tableau interactif ci-dessous.')
                    ->schema([
                        Placeholder::make('laragrid_table')
                            ->label('')
                            ->content(fn () => view('livewire.front-run-registration-grid-field')),
                    ]),
            ])
            ->statePath('data');
    }

    private function emptyElement(): array
    {
        return [
            '_k'                         => 'l' . bin2hex(random_bytes(4)),
            'first_name'                 => '',
            'last_name'                  => '',
            'birthdate'                  => null,
            'gender'                     => 'M',
            'nationality'                => 'SUI',
            'email'                      => '',
            'team'                       => '',
            'run_id'                     => '',
            'run_name'                   => '',
            'bloc'                       => '',
            'with_video'                 => false,
            'voucher_code'               => '',
            'address'                    => '',
            'address_extension'          => '',
            'postal_code'                => '',
            'locality'                   => '',
            'country'                    => 'SUI',
            'iban'                       => '',
            'payment_note'               => '',
            'has_free_registration_fee'  => false,
            'has_bonus_start'            => false,
            'bonus_start_amount'         => null,
            'bonus_ranking_amount'       => null,
            'bonus_arrival_amount'       => null,
            'has_accommodation'          => false,
            'accommodation_friday'       => false,
            'accommodation_saturday'     => false,
            'accommodation_precision'    => '',
            'has_expense_reimbursement'  => false,
            'expense_reimbursement_precision' => '',
        ];
    }

    protected function grids(): array
    {
        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', $this->type)
                ->orWhereNull('available_for_types');
        })->get();

        $runOptions = [];
        foreach ($runs as $r) {
            $cost = $r->provision?->product?->price?->amount ?? $r->cost;
            $runOptions[(string) $r->id] = $r->name . ' (' . ($cost ? $cost . ' CHF' : 'Gratuit') . ')';
        }

        $columns = [
            SerialColumn::make(),
            TextColumn::make('first_name')->label('Prénom')->grow(),
            TextColumn::make('last_name')->label('Nom')->grow(),
            DateColumn::make('birthdate')->label('Date de naissance (jj.mm.aaaa)')->width(150),
            SelectColumn::make('gender')->label('Genre')->options(['M' => 'M', 'F' => 'F'])->width(80),
        ];

        if (in_array($this->type, ['group', 'company'])) {
            $columns = array_merge($columns, [
                TextColumn::make('nationality')->label('Nationalité')->width(100),
                TextColumn::make('email')->label('Email')->grow(),
                SelectColumn::make('run_id')->label('Course')->options($runOptions)->grow(),
                CheckboxColumn::make('with_video')->label('Vidéo')->width(80),
            ]);
        }

        $grid = LaraGrid::make('elements')
            ->rowsFrom('elements')
            ->authorize(fn () => true);

        if (! $this->isGridLocked()) {
            $grid->editable()
                ->autoAppend()
                ->minRows(1)
                ->defaultRows(5)
                ->newRowUsing(fn () => $this->emptyElement());
        }

        $grid->columns($columns);

        return ['elements' => $grid];
    }

    public function addRow(): void
    {
        if (! $this->isGridLocked()) {
            $newRow = $this->emptyElement();
            $newRow['_k'] = 'l' . bin2hex(random_bytes(4));
            $this->elements[] = $newRow;
            $this->reseedGrid('elements', $this->elements);
        }
    }

    public function removeRow(int $index): void
    {
        if (! $this->isGridLocked() && isset($this->elements[$index])) {
            array_splice($this->elements, $index, 1);
            $this->reseedGrid('elements', $this->elements);
        }
    }

    public function isGridLocked(): bool
    {
        $deadline = setting('registrations_deadline');
        if (! $deadline) {
            return false;
        }

        return now()->greaterThan(Carbon::parse($deadline));
    }

    public function save()
    {
        $formData = $this->form->getState();

        $isNew = ! $this->registration || ! $this->registration->exists;

        if ($isNew) {
            $this->registration = new RunRegistration();
        }

        $this->registration->fill(array_merge($formData, [
            'run_registration_type' => $this->type,
        ]));

        $this->registration->save();

        if (! $this->isGridLocked()) {
            if ($this->type === 'elite') {
                // For Elite registrations, save the single runner directly from form state
                $run = ! empty($formData['elite_run_id']) ? Run::find($formData['elite_run_id']) : null;

                $runnerData = [
                    'first_name'                       => $formData['elite_first_name'] ?? null,
                    'last_name'                        => $formData['elite_last_name'] ?? null,
                    'birthdate'                        => $formData['elite_birthdate'] ?? null,
                    'gender'                           => $formData['elite_gender'] ?? 'M',
                    'nationality'                      => $formData['elite_nationality'] ?? 'SUI',
                    'email'                            => $formData['elite_email'] ?? null,
                    'run_id'                           => $formData['elite_run_id'] ?? null,
                    'run_name'                         => $run ? $run->name : null,
                    'bloc'                             => $formData['elite_bloc'] ?? null,
                    'address'                          => $formData['elite_address'] ?? null,
                    'address_extension'                => $formData['elite_address_extension'] ?? null,
                    'postal_code'                      => $formData['elite_postal_code'] ?? null,
                    'locality'                         => $formData['elite_locality'] ?? null,
                    'country'                          => $formData['elite_country'] ?? 'SUI',
                    'iban'                             => $formData['payment_iban'] ?? null,
                    'has_free_registration_fee'        => $formData['has_free_registration_fee'] ?? false,
                    'has_bonus_start'                  => $formData['has_bonus_start'] ?? false,
                    'bonus_start_amount'               => $formData['bonus_start_amount'] ?? null,
                    'bonus_ranking_amount'             => $formData['bonus_ranking_amount'] ?? null,
                    'bonus_arrival_amount'             => $formData['bonus_arrival_amount'] ?? null,
                    'has_accommodation'                => $formData['has_accommodation'] ?? false,
                    'accommodation_friday'             => $formData['accommodation_friday'] ?? false,
                    'accommodation_saturday'           => $formData['accommodation_saturday'] ?? false,
                    'accommodation_precision'          => $formData['accommodation_precision'] ?? null,
                    'has_expense_reimbursement'        => $formData['has_expense_reimbursement'] ?? false,
                    'expense_reimbursement_precision'  => $formData['expense_reimbursement_precision'] ?? null,
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
            } else {
                // For School, Group, Company: Sync LaraGrid spreadsheet rows
                $teamName = ($formData['company_name'] ?? null)
                    ?: (($formData['school_name'] ?? null)
                    ?: (($formData['contact_first_name'] ?? '') . ' ' . ($formData['contact_last_name'] ?? '')));

                $companyBloc = $formData['company_bloc'] ?? null;

                $cleanRows = $this->gridRows('elements');
                $keptIds = [];

                foreach ($cleanRows as $elementData) {
                    if (empty($elementData['first_name']) && empty($elementData['last_name'])) {
                        continue;
                    }

                    // Convert all empty strings to null to prevent MySQL integer/date type errors
                    foreach ($elementData as $key => $val) {
                        if ($val === '') {
                            $elementData[$key] = null;
                        }
                    }

                    $run = ! empty($elementData['run_id']) ? Run::find($elementData['run_id']) : null;
                    $elementData['run_name'] = $run ? $run->name : ($elementData['run_name'] ?? null);
                    $elementData['team'] = ! empty($elementData['team']) ? $elementData['team'] : $teamName;

                    if ($this->type === 'company' && $companyBloc) {
                        $elementData['bloc'] = $companyBloc;
                    }

                    $elementId = $elementData['id'] ?? null;
                    unset($elementData['_k'], $elementData['id']);

                    // Vérification & Consommation du Voucher
                    $voucherCode = trim($elementData['voucher_code'] ?? '');
                    $voucher = ! empty($voucherCode) ? \App\Models\Voucher::where('code', $voucherCode)->first() : null;

                    if ($voucher && (! $voucher->is_used || $voucher->used_by_run_registration_element_id === $elementId)) {
                        $elementData['has_free_registration_fee'] = true;
                    }

                    if ($elementId && $this->registration->runRegistrationElements()->withTrashed()->where('id', $elementId)->exists()) {
                        $existingEl = $this->registration->runRegistrationElements()->withTrashed()->find($elementId);
                        if ($existingEl->trashed()) {
                            $existingEl->restore();
                        }
                        $existingEl->update($elementData);
                        $targetEl = $existingEl;
                        $keptIds[] = $existingEl->id;
                    } else {
                        $newEl = $this->registration->runRegistrationElements()->create($elementData);
                        $targetEl = $newEl;
                        $keptIds[] = $newEl->id;
                    }

                    if ($voucher && (! $voucher->is_used || $voucher->used_by_run_registration_element_id === $targetEl->id)) {
                        $voucher->update([
                            'is_used'                             => true,
                            'used_at'                             => now(),
                            'used_by_run_registration_element_id' => $targetEl->id,
                            'client_id'                           => $this->registration->client_id ?? $voucher->client_id,
                        ]);
                    }
                }

                // Soft-delete elements that were removed from the grid by the user
                $this->registration->runRegistrationElements()->whereNotIn('id', $keptIds)->delete();
            }
        }

        if ($isNew) {
            try {
                $this->registration->notify(new RunRegistrationLink());
            } catch (\Throwable $e) {
                // Ignore mail dispatch errors
            }

            return redirect()->to(URL::signedRoute('front.run-registration.edit', [
                'registration' => $this->registration->id,
            ]))->with('message', 'Votre inscription a été créée et un email de confirmation contenant votre lien permanent d\'édition vous a été envoyé.');
        }

        session()->flash('message', $this->isGridLocked() 
            ? 'Les coordonnées générales ont été mises à jour. La liste des participants est verrouillée (délai dépassé).'
            : 'Inscription enregistrée avec succès.');
    }

    public function render(): View
    {
        return view('livewire.front-run-registration');
    }
}
