<?php

namespace App\Livewire;

use App\Models\Run;
use App\Models\Client;
use App\Models\School;
use Livewire\Component;
use Filament\Schemas\Schema;
use Livewire\WithFileUploads;
use App\Helpers\CountryHelper;
use Illuminate\Support\Carbon;
use App\Enums\SchoolClassLevel;
use App\Models\RunRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Rap2hpoutre\FastExcel\FastExcel;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use App\Notifications\RunRegistrationLink;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;

class FrontRunRegistration extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    /** @var array<int, int>|null */
    protected ?array $closedRunIdsCache = null;

    public ?array $data = [];

    public string $type = 'company';

    public $registration = null;

    public array $elements = [];

    public bool $isManager = false;

    public bool $integrityChecked = false;

    public array $integrityErrors = [];

    public bool $showImportModal = false;

    public string $pasteTextData = '';

    public $importFile = null;

    public function mount($type = null, ?RunRegistration $registration = null)
    {
        if (request()->routeIs('front.elite-manager')) {
            $this->isManager = true;
            $type = 'elite';
        }

        if ($type instanceof RunRegistration) {
            $registration = $type;
            $type = null;
        } elseif (request()->hasValidSignature() && (is_numeric($type) || (is_string($type) && ! in_array($type, ['company', 'school', 'group', 'elite'])))) {
            // Loading a registration by raw id is only allowed through a signed URL.
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

            $registrationType = $this->registration->run_registration_type?->value ?? $this->registration->type;
            if ($registrationType) {
                $type = is_object($registrationType) ? $registrationType->value : (string) $registrationType;
            }

            if ($type === 'elite') {
                return redirect()->to(URL::signedRoute('front.run-registration.elite-edit', [
                    'registration' => $this->registration->id,
                ]));
            }

            $registrationData = $this->registration->toArray();
            if ($type === 'school' && empty($registrationData['school_id']) && ! empty($registrationData['school_name'])) {
                $matchedSchool = School::where('name', $registrationData['school_name'])->first();
                $registrationData['school_id'] = $matchedSchool ? (string) $matchedSchool->id : 'other';
            }
            $this->form->fill($registrationData);
        } else {
            $initialData = [
                'school_country' => CountryHelper::DEFAULT,
            ];

            if ($clientId = request()->query('client_id')) {
                $client = Client::find($clientId);
                if ($client) {
                    $initialData = array_merge($initialData, [
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
                    ]);
                }
            }

            $this->form->fill($initialData);
        }

        $this->type = in_array($type, ['company', 'school', 'group', 'elite']) ? $type : 'company';

        $this->loadElements();
    }

    public function loadElements(): void
    {
        $elementsList = $this->registration
            ? $this->registration->runRegistrationElements()->get()
            : collect();

        if ($elementsList->isNotEmpty()) {
            $this->elements = $elementsList->map(function ($el) {
                $arr = array_merge($this->emptyElement(), $el->toArray());
                $arr['id'] = $el->id;
                $arr['_k'] = 'el_'.$el->id;
                if ($el->birthdate) {
                    $arr['birthdate'] = $el->birthdate->format('d.m.Y');
                }
                if ($el->gender) {
                    $arr['gender'] = is_object($el->gender) ? $el->gender->value : $el->gender;
                }
                $arr['run_id'] = $el->run_id ? (string) $el->run_id : '';
                if ($el->run) {
                    $cost = $el->run->provision?->product?->price?->amount ?? $el->run->cost;
                    $label = $el->run->name.' ('.($cost ? $cost.' CHF' : 'Gratuit').')';
                    if ($el->run->age_range_label) {
                        $label .= ' — '.$el->run->age_range_label;
                    }
                    $arr['_labels'] = ['run_id' => $label];
                }

                return $arr;
            })->toArray();
        } else {
            $this->elements = $this->defaultElements();
        }
    }

    public function defaultElements(): array
    {
        return [
            array_merge($this->emptyElement(), ['_k' => 'l1']),
            array_merge($this->emptyElement(), ['_k' => 'l2']),
            array_merge($this->emptyElement(), ['_k' => 'l3']),
        ];
    }

    public function getEstimatedTotalProperty(): float
    {
        return RunRegistration::calculateElementsEstimatedTotal($this->elements, $this->type);
    }

    public function form(Schema $schema): Schema
    {
        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', $this->type)
                ->orWhereNull('available_for_types');
        })->orderBy('name')->get();

        $runOptions = [];
        foreach ($runs as $r) {
            $cost = $r->provision?->product?->price?->amount ?? $r->cost;
            $runOptions[$r->id] = $r->name.' ('.($cost ? $cost.' CHF' : 'Gratuit').')';
        }

        $companyRun = RunRegistration::getCompanyRun();

        $rawBlocs = $companyRun?->start_blocs ?? [];
        $companyBlocOptions = [];

        if (is_array($rawBlocs)) {
            foreach ($rawBlocs as $b) {
                if (is_array($b)) {
                    $label = ($b['label'] ?? '').(! empty($b['time']) ? ' ('.$b['time'].')' : '');
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
                Hidden::make('client_id'),

                // SECTION GROUPE: Société / Club
                Section::make('Société ou Club')
                    ->icon('heroicon-m-user-group')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'group')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Société / Club')
                            ->placeholder('Ex: Club Athlétique Sion')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // SECTION SCHOOL 1: Centre scolaire et degré
                Section::make('Centre scolaire et degré')
                    ->icon('heroicon-m-academic-cap')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                    ->columns(4)
                    ->schema([
                        Select::make('school_id')
                            ->label('Établissement scolaire')
                            ->placeholder('Sélectionnez votre centre scolaire...')
                            ->options(function () {
                                $schools = School::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();
                                $schools['other'] = '➕ Autre établissement (saisie manuelle)';

                                return $schools;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && $state !== 'other') {
                                    $school = School::find($state);
                                    if ($school) {
                                        $set('school_name', $school->name);
                                        $set('school_postal_code', $school->postal_code);
                                        $set('school_locality', $school->locality);
                                        $set('school_country', CountryHelper::DEFAULT);
                                        if ($school->client_id) {
                                            $set('client_id', $school->client_id);
                                        }
                                    }
                                } elseif ($state === 'other') {
                                    $set('school_name', '');
                                }
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        Select::make('school_class_level')
                            ->label('Degré scolaire')
                            ->options(SchoolClassLevel::class)
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('school_name')
                            ->label('Nom de l\'établissement')
                            ->placeholder('Ex: Centre scolaire Pasteur')
                            ->required(fn ($get) => $get('school_id') === 'other')
                            ->visible(fn ($get) => $get('school_id') === 'other')
                            ->columnSpan(2),

                        TextInput::make('school_postal_code')
                            ->label('Code postal')
                            ->placeholder('1950')
                            ->numeric()
                            ->required()
                            ->columnSpan(fn ($get) => $get('school_id') === 'other' ? 1 : 2),

                        TextInput::make('school_locality')
                            ->label('Localité')
                            ->placeholder('Sion')
                            ->required()
                            ->columnSpan(fn ($get) => $get('school_id') === 'other' ? 1 : 2),
                    ]),

                // SECTION SCHOOL 2: Titulaire de la classe
                Section::make('Titulaire de la classe')
                    ->icon('heroicon-m-user-group')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                    ->columns(4)
                    ->schema([
                        TextInput::make('school_class_holder_first_name')
                            ->label('Prénom du titulaire')
                            ->columnSpan(2)
                            ->required(),

                        TextInput::make('school_class_holder_last_name')
                            ->label('Nom du titulaire')
                            ->columnSpan(2)
                            ->required(),

                        TextInput::make('school_class_holder_email')
                            ->label('E-mail du titulaire')
                            ->email()
                            ->columnSpan(2)
                            ->required(),

                        TextInput::make('school_class_holder_phone')
                            ->label('N° de téléphone portable')
                            ->tel()
                            ->columnSpan(2)
                            ->placeholder('079 123 45 67'),

                        TextInput::make('school_class_holder_address')
                            ->label('Adresse postale du titulaire')
                            ->placeholder('Rue de la Gare 1')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('school_class_holder_postal_code')
                            ->label('Code postal')
                            ->placeholder('1950')
                            ->required(),

                        TextInput::make('school_class_holder_locality')
                            ->label('Localité')
                            ->placeholder('Sion')
                            ->required(),
                    ]),

                // SECTION COMPANY: Entreprise
                Section::make('Entreprise dans la course')
                    ->icon('heroicon-m-building-office')
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'company')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nom de l\'entreprise ou du team')
                            ->helperText('Nom affiché sur les résultats.')
                            ->required(),

                        Select::make('company_bloc')
                            ->label('Bloc de départ souhaité pour l\'équipe')
                            ->helperText('Ce n\'est qu\'un souhait, nous essayerons de le prendre en compte au mieux.')
                            ->options($companyBlocOptions)
                            ->searchable(),
                    ]),

                // SECTION CONTACT: Personne responsable le jour de la course (School) / Personne de contact (Company, Group)
                Section::make(fn (FrontRunRegistration $livewire) => $livewire->type === 'school' ? 'Personne responsable le jour de la course' : 'Personne de contact')
                    ->icon('heroicon-m-user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_first_name')
                            ->label('Prénom de la personne de contact')
                            ->required(),

                        TextInput::make('contact_last_name')
                            ->label('Nom de la personne de contact')
                            ->required(),

                        TextInput::make('contact_email')
                            ->label('E-mail de contact')
                            ->email()
                            ->required(),

                        TextInput::make('contact_phone')
                            ->label('N° de téléphone portable')
                            ->tel()
                            ->placeholder('079 123 45 67')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),
                    ]),

                // SECTION FACTURATION (Entreprises & Groupes - pas pour Écoles)
                Section::make('Facturation')
                    ->icon('heroicon-m-credit-card')
                    ->columns(4)
                    ->collapsible()
                    ->visible(fn (FrontRunRegistration $livewire) => $livewire->type !== 'school')
                    ->schema([
                        TextInput::make('invoicing_company_name')
                            ->label('Nom de l\'entreprise / Raison sociale de facturation')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('invoicing_address')
                            ->required()
                            ->label('Adresse'),

                        TextInput::make('invoicing_address_extension')
                            ->label('Complément d\'adresse'),

                        TextInput::make('invoicing_postal_code')
                            ->required()
                            ->label('Code postal'),

                        TextInput::make('invoicing_locality')
                            ->required()
                            ->label('Localité'),

                        TextInput::make('invoicing_email')
                            ->label('Email d\'envoi de la facture')
                            ->email()
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('invoicing_note')
                            ->label('Remarque sur la facturation')
                            ->columnSpanFull(),
                    ]),

                // SECTION PARTICIPANTS
                Section::make('Participants à inscrire')
                    ->icon('heroicon-m-table-cells')
                    ->description('Remplissez les informations de chaque participant dans le tableau ci-dessous.')
                    ->schema([
                        ViewField::make('laragrid_table')
                            ->view('livewire.front-run-registration-grid-field'),
                    ]),
            ])
            ->statePath('data');
    }

    private function emptyElement(): array
    {
        return [
            '_k'                => 'l'.bin2hex(random_bytes(4)),
            '_actions'          => ['delete' => true],
            'first_name'        => '',
            'last_name'         => '',
            'birthdate'         => null,
            'gender'            => 'M',
            'nationality'       => 'SUI',
            'email'             => '',
            'team'              => '',
            'run_id'            => '',
            'run_name'          => '',
            'bloc'              => '',
            'with_video'        => false,
            'voucher_code'      => '',
            'address'           => '',
            'address_extension' => '',
            'postal_code'       => '',
            'locality'          => '',
            'country'           => 'SUI',
        ];
    }

    public function verifyIntegrity(): array
    {
        $this->integrityChecked = true;
        $this->integrityErrors = [];

        foreach ($this->elements as $idx => $row) {
            $rowNum = $idx + 1;
            $firstName = trim($row['first_name'] ?? '');
            $lastName = trim($row['last_name'] ?? '');
            $birthdate = trim($row['birthdate'] ?? '');
            $email = trim($row['email'] ?? '');
            $gender = trim($row['gender'] ?? '');
            $runId = trim((string) ($row['run_id'] ?? ''));

            if ($firstName === '' && $lastName === '' && $birthdate === '' && $email === '' && $runId === '') {
                continue;
            }

            $rowErrors = [];

            if ($firstName === '') {
                $rowErrors[] = 'Prénom manquant';
            }
            if ($lastName === '') {
                $rowErrors[] = 'Nom manquant';
            }

            if ($birthdate === '') {
                $rowErrors[] = 'Date de naissance manquante';
            } else {
                $validDate = false;
                if (preg_match('/^(\d{1,2})[\.\/-](\d{1,2})[\.\/-](\d{4})$/', $birthdate, $m)) {
                    $validDate = checkdate((int) $m[2], (int) $m[1], (int) $m[3]);
                } elseif (preg_match('/^(\d{4})[\.\/-](\d{1,2})[\.\/-](\d{1,2})$/', $birthdate, $m)) {
                    $validDate = checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
                }

                if (! $validDate) {
                    $rowErrors[] = 'Date de naissance invalide (format attendu : jj.mm.aaaa)';
                }
            }

            if (! in_array($gender, ['M', 'F'], true)) {
                $rowErrors[] = 'Genre invalide (M ou F attendu)';
            }

            if ($this->type === 'group') {
                if ($email === '') {
                    $rowErrors[] = 'E-mail manquant';
                } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Format d\'e-mail invalide';
                }
                if ($runId === '') {
                    $rowErrors[] = 'Course non sélectionnée';
                } elseif (in_array((int) $runId, $this->closedGroupRunIds(), true) && ! $this->isPersistedOnSameRun($row)) {
                    $closedRun = Run::find((int) $runId);
                    $rowErrors[] = 'Inscriptions closes pour la course "'.$closedRun?->name.'" (clôture le '.$closedRun?->effectiveRegistrationsDeadline()?->format('d.m.Y \à H:i').')';
                }
            } elseif ($this->type === 'company') {
                if ($email === '') {
                    $rowErrors[] = 'E-mail manquant';
                } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Format d\'e-mail invalide';
                }
            }

            // Age verification against target Run (for all types: group, company, school)
            $calculatedAge = $this->calculateAge($birthdate);
            if ($calculatedAge !== null) {
                $targetRunId = $runId !== '' ? (int) $runId : null;
                if (! $targetRunId) {
                    if ($this->type === 'company') {
                        $targetRunId = setting('default_run_company') ?: Run::where(function ($q) {
                            $q->whereJsonContains('available_for_types', 'company')->orWhereNull('available_for_types');
                        })->first()?->id;
                    } elseif ($this->type === 'school') {
                        $targetRunId = setting('default_run_school') ?: Run::where(function ($q) {
                            $q->whereJsonContains('available_for_types', 'school')->orWhereNull('available_for_types');
                        })->first()?->id;
                    }
                }

                if ($targetRunId) {
                    $selectedRun = Run::find((int) $targetRunId);
                    if ($selectedRun) {
                        if ($selectedRun->min_age !== null && $calculatedAge < $selectedRun->min_age) {
                            $rowErrors[] = "Âge insuffisant pour la course \"{$selectedRun->name}\" (âge minimum : {$selectedRun->min_age} ans, âge calculé : {$calculatedAge} ans)";
                        }
                        if ($selectedRun->max_age !== null && $calculatedAge > $selectedRun->max_age) {
                            $rowErrors[] = "Âge supérieur à la limite pour la course \"{$selectedRun->name}\" (âge maximum : {$selectedRun->max_age} ans, âge calculé : {$calculatedAge} ans)";
                        }
                        if ($gender !== '' && ! $selectedRun->matchesGender($gender)) {
                            $rowErrors[] = "Course \"{$selectedRun->name}\" non ouverte au genre sélectionné ({$gender})";
                        }
                    }
                }
            }

            if (! empty($rowErrors)) {
                $label = ($firstName || $lastName) ? "$firstName $lastName" : "Ligne #$rowNum";
                $this->integrityErrors[] = [
                    'row'    => $rowNum,
                    'label'  => $label,
                    'errors' => $rowErrors,
                ];
            }
        }

        return $this->integrityErrors;
    }

    public function calculateAge(?string $birthdate): ?int
    {
        if (empty($birthdate)) {
            return null;
        }

        $birthdate = trim($birthdate);
        $eventYear = (int) now()->format('Y');

        if (preg_match('/^(\d{1,2})[\.\/-](\d{1,2})[\.\/-](\d{4})$/', $birthdate, $m)) {
            if (checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
                return max(0, $eventYear - (int) $m[3]);
            }
        } elseif (preg_match('/^(\d{4})[\.\/-](\d{1,2})[\.\/-](\d{1,2})$/', $birthdate, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return max(0, $eventYear - (int) $m[1]);
            }
        } elseif (preg_match('/^(\d{4})$/', $birthdate, $m)) {
            $year = (int) $m[1];
            if ($year >= 1900 && $year <= $eventYear + 1) {
                return max(0, $eventYear - $year);
            }
        } elseif (preg_match('/^(\d{2})$/', $birthdate, $m)) {
            $year = (int) $m[1];
            $fullYear = $year <= (int) now()->format('y') ? 2000 + $year : 1900 + $year;

            return max(0, $eventYear - $fullYear);
        }

        return null;
    }

    public function getRunsForBirthdate(?string $birthdate, ?string $gender = null, bool $withClosed = false): array
    {
        $age = $this->calculateAge($birthdate);

        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', 'group')
                ->orWhereNull('available_for_types');
        })->orderBy('name')->get();

        $options = [];
        foreach ($runs as $r) {
            if ($age !== null && ! $r->matchesAge($age)) {
                continue;
            }

            if (! empty($gender) && ! $r->matchesGender($gender)) {
                continue;
            }

            if (! $withClosed && ! $r->isRegistrationOpen()) {
                continue;
            }

            $cost = $r->provision?->product?->price?->amount ?? $r->cost;
            $costLabel = ($cost > 0) ? number_format((float) $cost, 2, '.', '').' CHF' : 'Gratuit';

            $label = $r->name.' ('.$costLabel.')';
            if ($r->age_range_label) {
                $label .= ' — '.$r->age_range_label;
            }
            if (! $r->isRegistrationOpen()) {
                $label .= ' — inscriptions closes';
            }

            $options[(string) $r->id] = $label;
        }

        return $options;
    }

    public function getSchoolTeamStatsProperty(): array
    {
        $minStudents = (int) config('cdn.interclasses.min_students', 8);
        $minGirls = (int) config('cdn.interclasses.min_girls', 3);

        $validElements = array_filter($this->elements, fn ($r) => ! empty(trim($r['first_name'] ?? '')) || ! empty(trim($r['last_name'] ?? '')));
        $total = count($validElements);
        $girls = count(array_filter($validElements, fn ($r) => strtoupper(trim((string) ($r['gender'] ?? ''))) === 'F'));
        $boys = count(array_filter($validElements, fn ($r) => strtoupper(trim((string) ($r['gender'] ?? ''))) === 'M'));

        return [
            'total'            => $total,
            'girls'            => $girls,
            'boys'             => $boys,
            'min_students'     => $minStudents,
            'min_girls'        => $minGirls,
            'is_conform'       => ($total >= $minStudents && $girls >= $minGirls),
            'missing_students' => max(0, $minStudents - $total),
            'missing_girls'    => max(0, $minGirls - $girls),
        ];
    }

    public function updated($propertyName, $value = null): void
    {
        if (str_starts_with((string) $propertyName, 'elements.')) {
            $this->normalizeElementsCourseOptions();
        }
    }

    public function updatedElements(): void
    {
        $this->normalizeElementsCourseOptions();
    }

    private function normalizeElementsCourseOptions(): void
    {
        if (! is_array($this->elements)) {
            return;
        }

        // Drop ghost rows: a late wire:model update targeting an index that no longer
        // exists (after a row deletion) would create a partial row without a `_k`.
        $this->elements = array_values(array_filter(
            $this->elements,
            fn ($r) => is_array($r) && ! empty($r['_k'])
        ));

        if (empty($this->elements)) {
            $this->elements = $this->defaultElements();
        }

        foreach ($this->elements as &$row) {
            $birthdate = trim((string) ($row['birthdate'] ?? ''));
            $gender = trim((string) ($row['gender'] ?? ''));
            $runId = trim((string) ($row['run_id'] ?? ''));

            if ($this->type === 'group') {
                $availableRuns = $this->getRunsForBirthdate($birthdate, $gender);

                // If current selected run is no longer valid for age + gender, reset it (une course close reste en place, la ligne est verrouillée)
                if ($runId !== '' && ! isset($availableRuns[$runId]) && ! isset($this->getRunsForBirthdate($birthdate, $gender, true)[$runId])) {
                    $row['run_id'] = '';
                }

                // If no run selected (or just reset) and there is exactly one available run, auto-select it!
                if ($row['run_id'] === '' && count($availableRuns) === 1) {
                    $row['run_id'] = (string) array_key_first($availableRuns);
                }
            }
        }
        unset($row);

        if ($this->integrityChecked) {
            $this->verifyIntegrity();
        }
    }

    public function deleteRowByRow(array $row): void
    {
        $key = $row['_k'] ?? null;
        if ($key) {
            $this->elements = array_values(array_filter($this->elements, fn ($r) => ($r['_k'] ?? null) !== $key));
            if (empty($this->elements)) {
                $this->elements = $this->defaultElements();
            }
        }
    }

    public function addRow(): void
    {
        if (! $this->isGridLocked()) {
            $newRow = $this->emptyElement();
            $newRow['_k'] = 'l'.bin2hex(random_bytes(4));
            $this->elements[] = $newRow;
        }
    }

    public function removeRowByKey(string $key): void
    {
        if ($this->isGridLocked()) {
            return;
        }

        foreach ($this->elements as $r) {
            if (($r['_k'] ?? null) === $key && $this->isRowLocked($r)) {
                return;
            }
        }

        $this->elements = array_values(array_filter(
            $this->elements,
            fn ($r) => ($r['_k'] ?? null) !== $key
        ));

        if (empty($this->elements)) {
            $this->elements = $this->defaultElements();
        }
    }

    public function removeRow(int $index): void
    {
        if (! $this->isGridLocked() && isset($this->elements[$index]) && ! $this->isRowLocked($this->elements[$index])) {
            array_splice($this->elements, $index, 1);
            $this->elements = array_values($this->elements);

            if (empty($this->elements)) {
                $this->elements = $this->defaultElements();
            }
        }
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->pasteTextData = '';
        $this->importFile = null;
    }

    public function processPasteText(): void
    {
        if (trim($this->pasteTextData) === '') {
            return;
        }

        $this->resetErrorBag(['pasteTextData', 'importFile']);

        $this->applyImport($this->parseDelimitedText($this->pasteTextData), 'pasteTextData');
    }

    /** Modèle Excel à remplir puis à réimporter (mêmes en-têtes que ceux attendus par l'import). */
    public function downloadImportTemplate()
    {
        $courseExample = $this->type === 'group'
            ? (Run::where(fn ($q) => $q->whereJsonContains('available_for_types', 'group')->orWhereNull('available_for_types'))->orderBy('name')->value('name') ?? '')
            : '';

        $rows = collect([
            ['Prénom' => 'Lucas', 'Nom' => 'Rey', 'Date de naissance' => '02.02.2014', 'Genre' => 'M', 'Email' => 'lucas@example.com', 'Nationalité' => 'SUI', 'Course' => $courseExample],
            ['Prénom' => 'Emma', 'Nom' => 'Dubois', 'Date de naissance' => '15.08.2012', 'Genre' => 'F', 'Email' => '', 'Nationalité' => 'SUI', 'Course' => ''],
        ]);

        return (new FastExcel($rows))->download('modele_import_participants.xlsx');
    }

    public function processExcelImport(): void
    {
        if (! $this->importFile) {
            return;
        }

        $this->resetErrorBag(['pasteTextData', 'importFile']);

        $this->validate([
            'importFile' => ['file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'importFile.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
            'importFile.max'   => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        $filePath = $this->importFile->getRealPath();
        $extension = strtolower((string) $this->importFile->getClientOriginalExtension());

        try {
            if (in_array($extension, ['csv', 'txt'], true)) {
                $content = (string) file_get_contents($filePath);
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                if (! mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
                }
                $rawRows = $this->parseDelimitedText($content, false);
            } else {
                $rawRows = (new FastExcel)->withoutHeaders()->import($filePath)
                    ->map(fn ($r) => array_values(is_array($r) ? $r : $r->toArray()))
                    ->all();
            }
        } catch (\Throwable $e) {
            report($e);
            $this->addError('importFile', 'Impossible de lire ce fichier. Vérifiez qu\'il s\'agit bien d\'un fichier Excel valide.');

            return;
        }

        $this->applyImport(array_values($rawRows), 'importFile');
    }

    /**
     * Point d'entrée commun (Excel, CSV, copier-coller) : transforme les lignes brutes en participants,
     * les ajoute à la grille et indique à l'utilisateur ce qui a été importé / ignoré.
     *
     * @param  array<int, array<int, mixed>>  $rawRows
     */
    protected function applyImport(array $rawRows, string $errorField): void
    {
        $result = $this->mapImportRows($rawRows);

        if ($result['error'] !== null || empty($result['rows'])) {
            $this->addError($errorField, $result['error'] ?? 'Aucun participant reconnu. Colonnes attendues : Prénom, Nom, Date de naissance, Genre, Email, Nationalité, Course. Téléchargez le modèle Excel ci-dessus pour partir d\'un fichier correct.');

            return;
        }

        $newRows = [];
        $unknownRuns = 0;
        foreach ($result['rows'] as $normalized) {
            $row = $this->emptyElement();
            $row['_k'] = 'l'.bin2hex(random_bytes(4));
            $row['first_name'] = $normalized['first_name'];
            $row['last_name'] = $normalized['last_name'];
            $row['birthdate'] = $normalized['birthdate'];
            $row['gender'] = $normalized['gender'];
            $row['email'] = $normalized['email'];
            $row['nationality'] = $normalized['nationality'] !== '' ? $normalized['nationality'] : 'SUI';

            if ($this->type === 'group') {
                $availableRuns = $this->getRunsForBirthdate($row['birthdate'], $row['gender'] ?? 'M');
                $matched = $normalized['run'] !== '' ? $this->matchImportedRun($normalized['run'], array_keys($availableRuns)) : null;

                if ($matched !== null) {
                    $row['run_id'] = $matched;
                } else {
                    if ($normalized['run'] !== '') {
                        $unknownRuns++;
                    }
                    if (count($availableRuns) === 1) {
                        $row['run_id'] = (string) array_key_first($availableRuns);
                    }
                }
            }

            $newRows[] = $row;
        }

        $this->elements = array_values(array_filter($this->elements, function ($r) {
            return ! empty(trim($r['first_name'] ?? '')) ||
                   ! empty(trim($r['last_name'] ?? '')) ||
                   ! empty(trim($r['birthdate'] ?? '')) ||
                   ! empty(trim($r['email'] ?? ''));
        }));
        $this->elements = array_merge($this->elements, $newRows);
        $this->closeImportModal();

        $count = count($newRows);
        $message = $count.' '.($count > 1 ? 'participants importés' : 'participant importé');
        if (! empty($result['skipped'])) {
            $message .= ' — '.count($result['skipped']).' ligne(s) ignorée(s) (n° '.implode(', ', array_slice($result['skipped'], 0, 10)).(count($result['skipped']) > 10 ? '…' : '').').';
        }
        if ($unknownRuns > 0) {
            $message .= ' '.$unknownRuns.' course(s) non reconnue(s) ou incompatible(s) avec l\'âge/le genre : à choisir à la main.';
        }
        session()->flash('message', $message);
    }

    /**
     * Découpe un texte copié depuis Excel / un CSV en lignes de cellules. Le séparateur est détecté UNE fois
     * pour tout le texte (tabulation > ; > | > ,), les cellules vides sont conservées (sinon les colonnes se décalent).
     *
     * @return array<int, array<int, string>>
     */
    protected function parseDelimitedText(string $text, bool $allowSpaces = true): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_values(array_filter(explode("\n", $text), fn ($l) => trim($l) !== ''));
        if (empty($lines)) {
            return [];
        }

        $separator = null;
        foreach (["\t", ';', '|', ','] as $candidate) {
            if (str_contains(implode("\n", $lines), $candidate)) {
                // Un séparateur n'est retenu que s'il figure sur au moins la moitié des lignes
                $withIt = count(array_filter($lines, fn ($l) => str_contains($l, $candidate)));
                if ($withIt >= max(1, (int) ceil(count($lines) / 2))) {
                    $separator = $candidate;
                    break;
                }
            }
        }

        $rows = [];
        if ($separator === null) {
            if (! $allowSpaces) {
                return array_map(fn ($l) => [trim($l)], $lines);
            }
            foreach ($lines as $line) {
                $rows[] = preg_split('/\s+/', trim($line));
            }

            return $rows;
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, implode("\n", $lines));
        rewind($stream);
        while (($cells = fgetcsv($stream, 0, $separator, '"', '')) !== false) {
            if ($cells === [null]) {
                continue;
            }
            $cells = array_map(fn ($c) => trim((string) $c), $cells);
            if (implode('', $cells) === '') {
                continue;
            }
            $rows[] = $cells;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Transforme des lignes brutes en participants normalisés.
     *
     * - L'en-tête est la 1re ligne (parmi les 50 premières) qui contient au moins 2 colonnes reconnues dont un prénom ou un nom :
     *   un simple « Nom » perdu dans un bloc d'infos n'en est pas un. Les colonnes peuvent être dans n'importe quel ordre.
     * - Sans en-tête, l'ordre Prénom, Nom, Naissance, Genre, E-mail, Nationalité, Course est supposé SI les données y ressemblent.
     * - Les lignes qui ne ressemblent pas à un participant (libellés, totaux, vides) sont ignorées et comptées.
     *
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array{rows: array<int, array<string, string>>, skipped: array<int, int>, error: ?string}
     */
    protected function mapImportRows(array $rawRows): array
    {
        $aliases = [
            'first_name'  => ['prenom', 'first name', 'firstname', 'first_name', 'prenom eleve', 'prenom de l eleve', 'prenom participant'],
            'last_name'   => ['nom', 'last name', 'lastname', 'last_name', 'nom de famille', 'nom eleve', 'nom de l eleve', 'nom participant', 'surname', 'family name'],
            'birthdate'   => ['date de naissance', 'date naissance', 'naissance', 'birthdate', 'date', 'ne le', 'nee le', 'annee de naissance', 'annee naissance', 'annee', 'date of birth', 'dob', 'birth', 'ddn'],
            'gender'      => ['genre', 'sexe', 'gender', 'sex', 'm f', 'h f', 'm w', 'categorie sexe'],
            'email'       => ['email', 'e mail', 'mail', 'courriel', 'adresse email', 'adresse e mail', 'adresse mail', 'email participant'],
            'nationality' => ['nationalite', 'nationality', 'pays', 'country', 'nat'],
            'run'         => ['course', 'parcours', 'epreuve', 'race', 'distance', 'categorie', 'course choisie', 'course souhaitee'],
        ];

        $canon = function ($v): string {
            $v = $this->importCellToString($v);
            $v = preg_replace('/\(.*?\)/u', ' ', $v);
            $v = mb_strtolower(\Illuminate\Support\Str::ascii($v));

            return trim(preg_replace('/[^a-z0-9]+/', ' ', $v));
        };

        $headerIndex = null;
        $map = [];
        foreach (array_slice($rawRows, 0, 50, true) as $i => $cells) {
            $found = [];
            foreach ($cells as $col => $cell) {
                $c = $canon($cell);
                if ($c === '') {
                    continue;
                }
                foreach ($aliases as $field => $names) {
                    if (! isset($found[$field]) && in_array($c, $names, true)) {
                        $found[$field] = $col;
                        break;
                    }
                }
            }
            if (count($found) >= 2 && (isset($found['first_name']) || isset($found['last_name']))) {
                $headerIndex = $i;
                $map = $found;
                break;
            }
        }

        if ($headerIndex === null) {
            // Pas d'en-têtes : on n'accepte l'ordre par défaut que si la majorité des lignes ressemble à des participants
            $candidates = array_filter($rawRows, fn ($cells) => count(array_filter($cells, fn ($c) => $this->importCellToString($c) !== '')) >= 2);
            $plausible = array_filter($candidates, function ($cells) {
                $first = $this->importCellToString($cells[0] ?? '');
                $last = $this->importCellToString($cells[1] ?? '');
                $birth = $this->importCellToString($cells[2] ?? '');

                return $first !== '' && $last !== '' && ! is_numeric($first) && ! is_numeric($last)
                    && ! str_contains($first, '@') && ! str_contains($last, '@')
                    && ($birth === '' || $this->normalizeImportDate($birth) !== null);
            });

            if (empty($candidates) || count($plausible) < max(1, (int) ceil(count($candidates) * 0.7))) {
                return ['rows' => [], 'skipped' => [], 'error' => 'Aucune ligne d\'en-têtes reconnue (Prénom, Nom, Date de naissance…) et les données ne ressemblent pas à une liste de participants. Téléchargez le modèle Excel ci-dessus et collez-y vos participants.'];
            }

            $headerIndex = -1;
            $map = ['first_name' => 0, 'last_name' => 1, 'birthdate' => 2, 'gender' => 3, 'email' => 4, 'nationality' => 5, 'run' => 6];
        }

        $out = [];
        $skipped = [];
        foreach ($rawRows as $i => $cells) {
            if ($i <= $headerIndex) {
                continue;
            }
            $get = fn (string $f) => isset($map[$f]) ? $this->importCellToString($cells[$map[$f]] ?? '') : '';

            $first = $get('first_name');
            $last = $get('last_name');
            $birthRaw = $get('birthdate');
            $birth = $this->normalizeImportDate($birthRaw);
            $email = $this->extractImportEmail($get('email'));

            // En mode sans en-tête, un e-mail rangé dans une autre colonne est quand même retrouvé
            if ($email === '' && $headerIndex === -1) {
                foreach ($cells as $cell) {
                    if (($found = $this->extractImportEmail($this->importCellToString($cell))) !== '') {
                        $email = $found;
                        break;
                    }
                }
            }

            if ($first === '' && $last === '' && $birthRaw === '' && $email === '') {
                continue; // ligne vide
            }

            $isParticipant = ($first !== '' && $last !== '')
                || (($first !== '' || $last !== '') && ($birth !== null && $birth !== '' || $email !== ''));
            if (! $isParticipant) {
                $skipped[] = $i + 1;

                continue;
            }

            $out[] = [
                'first_name'  => $first,
                'last_name'   => $last,
                'birthdate'   => $birth ?? $birthRaw,
                'gender'      => $this->importGender($get('gender')),
                'email'       => $email,
                'nationality' => $this->importNationality($get('nationality')),
                'run'         => $get('run'),
            ];
        }

        return ['rows' => $out, 'skipped' => $skipped, 'error' => null];
    }

    /** Rapproche une valeur importée (nom, code ou code Datasport) d'une course autorisée ; null si absente ou ambiguë. */
    protected function matchImportedRun(string $value, array $allowedRunIds): ?string
    {
        $canon = fn (string $v) => trim(preg_replace('/[^a-z0-9+\-.]+/', ' ', mb_strtolower(\Illuminate\Support\Str::ascii($v))));
        $needle = $canon($value);
        if ($needle === '' || empty($allowedRunIds)) {
            return null;
        }

        $runs = Run::whereIn('id', $allowedRunIds)->get();

        foreach ($runs as $run) {
            foreach ([$run->name, $run->code, $run->datasport_code] as $candidate) {
                if ($candidate !== null && $candidate !== '' && $canon((string) $candidate) === $needle) {
                    return (string) $run->id;
                }
            }
        }

        $partial = $runs->filter(fn ($run) => str_contains($canon((string) $run->name), $needle) || str_contains($needle, $canon((string) $run->name)));

        return $partial->count() === 1 ? (string) $partial->first()->id : null;
    }

    /** Normalise une date en jj.mm.aaaa (ou une année seule) ; '' si vide ; null si non reconnue. */
    protected function normalizeImportDate(string $v): ?string
    {
        $v = trim(preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?.*$/', '', trim($v)));
        if ($v === '') {
            return '';
        }

        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4}|\d{2})$/', $v, $m)) {
            $year = (int) $m[3];
            if (strlen($m[3]) === 2) {
                $year += $year <= (int) now()->format('y') ? 2000 : 1900;
            }
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                return sprintf('%02d.%02d.%04d', $m[1], $m[2], $year);
            }

            return null;
        }
        if (preg_match('/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})$/', $v, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? sprintf('%02d.%02d.%04d', $m[3], $m[2], $m[1]) : null;
        }
        if (preg_match('/^(\d{4})$/', $v, $m) && (int) $m[1] >= 1900 && (int) $m[1] <= (int) now()->format('Y') + 1) {
            return $v;
        }

        return null;
    }

    protected function extractImportEmail(string $v): string
    {
        return preg_match('/[^\s,;<>"\']+@[^\s,;<>"\']+\.[A-Za-z]{2,}/', $v, $m) ? $m[0] : '';
    }

    protected function importNationality(string $v): string
    {
        $v = trim($v);
        if ($v === '') {
            return '';
        }

        $options = CountryHelper::getOptions();
        if (isset($options[strtoupper($v)])) {
            return strtoupper($v);
        }

        $fr = ['suisse' => 'SUI', 'switzerland' => 'SUI', 'schweiz' => 'SUI', 'france' => 'FRA', 'italie' => 'ITA', 'italy' => 'ITA',
            'allemagne' => 'GER', 'germany' => 'GER', 'espagne' => 'ESP', 'spain' => 'ESP', 'portugal' => 'POR'];
        $key = mb_strtolower(\Illuminate\Support\Str::ascii($v));
        if (isset($fr[$key])) {
            return $fr[$key];
        }
        foreach ($options as $code => $label) {
            if (mb_strtolower(\Illuminate\Support\Str::ascii(trim(preg_replace('/\(.*?\)/', '', $label)))) === $key) {
                return $code;
            }
        }

        return '';
    }

    protected function importCellToString(mixed $v): string
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->format('d.m.Y');
        }
        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            // Numéro de série Excel d'une date (ex. 43133 = 02.02.2018) ; une année seule (2018) reste telle quelle
            if ($v >= 10000 && $v <= 80000 && (float) $v == (int) $v) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $v)->format('d.m.Y');
            }
        }
        if (is_scalar($v) || $v === null) {
            return trim((string) $v);
        }

        return '';
    }

    protected function importGender(string $v): string
    {
        $v = mb_strtolower(\Illuminate\Support\Str::ascii(trim($v)));

        return in_array($v, ['f', 'fille', 'femme', 'feminin', 'female', 'w', 'weiblich', 'madchen', 'dame', 'dames', 'girl', 'woman'], true) ? 'F' : 'M';
    }

    public function cleanEmptyRows(): void
    {
        $this->elements = array_values(array_filter($this->elements, function ($r) {
            return ! empty(trim($r['first_name'] ?? '')) ||
                   ! empty(trim($r['last_name'] ?? '')) ||
                   ! empty(trim($r['birthdate'] ?? '')) ||
                   ! empty(trim($r['email'] ?? ''));
        }));

        if (empty($this->elements)) {
            $this->elements = $this->defaultElements();
        }

        $this->integrityChecked = false;
        $this->integrityErrors = [];
        session()->flash('message', 'Les lignes vides ont été nettoyées avec succès.');
    }

    /** Délai global dépassé : plus rien n'est modifiable. */
    public function isGlobalDeadlinePassed(): bool
    {
        $deadline = Run::globalRegistrationsDeadline();

        // Le délai est saisi en heure suisse : sans fuseau explicite, il serait lu en UTC et la grille se verrouillerait 1 à 2 h trop tard
        return $deadline !== null && now()->greaterThan($deadline);
    }

    /** Course imposée aux inscriptions école / entreprise (ces formulaires n'ont pas de choix de course par ligne). */
    protected function defaultRunForType(): ?Run
    {
        return match ($this->type) {
            'company' => RunRegistration::getCompanyRun(),
            'school'  => RunRegistration::getSchoolRun(),
            default   => null,
        };
    }

    /** Course école/entreprise clôturée (son délai propre est dépassé) : le formulaire entier est verrouillé. */
    public function isDefaultRunClosed(): bool
    {
        $run = $this->defaultRunForType();

        return $run !== null && ! $run->isRegistrationOpen();
    }

    public function isGridLocked(): bool
    {
        return $this->isGlobalDeadlinePassed() || $this->isDefaultRunClosed();
    }

    /** Message expliquant pourquoi le tableau est verrouillé (null si ouvert). */
    public function gridLockMessage(): ?array
    {
        if ($this->isGlobalDeadlinePassed()) {
            return [
                'title' => 'Le délai d\'inscription est dépassé',
                'text'  => 'La liste des participants ne peut plus être modifiée (clôture le '.Run::globalRegistrationsDeadline()->format('d.m.Y \à H:i').'). Pour une modification, contactez l\'organisation.',
            ];
        }

        if ($this->isDefaultRunClosed()) {
            $run = $this->defaultRunForType();

            return [
                'title' => 'Les inscriptions pour cette course sont closes',
                'text'  => 'Le délai pour « '.$run->name.' » est dépassé (clôture le '.$run->effectiveRegistrationsDeadline()->format('d.m.Y \à H:i').'). Pour une modification, contactez l\'organisation.',
            ];
        }

        return null;
    }

    /** @return array<int, int> */
    protected function closedGroupRunIds(): array
    {
        return $this->closedRunIdsCache ??= Run::where(function ($q) {
            $q->whereJsonContains('available_for_types', 'group')->orWhereNull('available_for_types');
        })->get()->reject(fn ($r) => $r->isRegistrationOpen())->pluck('id')->all();
    }

    /** @return array<int, int> */
    public function closedRunIdsForView(): array
    {
        return $this->type === 'group' ? $this->closedGroupRunIds() : [];
    }

    /** Ligne non modifiable : tableau verrouillé, ou (groupes) course de la ligne clôturée. */
    public function isRowLocked(array $row): bool
    {
        if ($this->isGridLocked()) {
            return true;
        }

        $runId = (int) ($row['run_id'] ?? 0);

        return $this->type === 'group' && $runId > 0 && in_array($runId, $this->closedGroupRunIds(), true);
    }

    /** Une ligne déjà enregistrée sur cette course clôturée peut rester telle quelle ; toute autre ligne sur une course close est refusée. */
    protected function isPersistedOnSameRun(array $row): bool
    {
        $id = $row['id'] ?? null;

        return $id && $this->registration
            && $this->registration->runRegistrationElements()->where('id', $id)->where('run_id', $row['run_id'] ?? null)->exists();
    }

    public function save()
    {
        $errors = $this->isGridLocked() ? [] : $this->verifyIntegrity();
        if (count($errors) > 0) {
            session()->flash('message', '⚠️ Impossible d\'enregistrer : '.count($errors).' participant(s) contiennent des anomalies. Veuillez consulter le rapport de vérification ci-dessous.');

            return;
        }

        $formData = $this->form->getState();

        if ($this->type === 'school') {
            $formData['school_country'] = CountryHelper::DEFAULT;
            $schoolId = $formData['school_id'] ?? null;
            $schoolName = trim((string) ($formData['school_name'] ?? ''));

            if (($schoolId === 'other' || empty($schoolId)) && ! empty($schoolName)) {
                $school = School::firstOrCreate(
                    ['name' => $schoolName],
                    [
                        'postal_code' => $formData['school_postal_code'] ?? null,
                        'locality'    => $formData['school_locality'] ?? null,
                        'country'     => CountryHelper::DEFAULT,
                    ]
                );
                $formData['school_id'] = $school->id;
                if ($school->client_id && empty($formData['client_id'])) {
                    $formData['client_id'] = $school->client_id;
                }
            } elseif ($schoolId && $schoolId !== 'other') {
                $school = School::find((int) $schoolId);
                if ($school) {
                    $formData['school_name'] = $school->name;
                    if ($school->client_id && empty($formData['client_id'])) {
                        $formData['client_id'] = $school->client_id;
                    }
                }
            }
        }

        $isNew = ! $this->registration || ! $this->registration->exists;

        if ($isNew) {
            $this->registration = new RunRegistration;
        }

        $this->registration->fill(array_merge($formData, [
            'run_registration_type' => $this->type,
        ]));

        $this->registration->save();

        // Sync client invoicing data if linked
        if ($this->registration->client_id) {
            $client = Client::find($this->registration->client_id);
            if ($client) {
                $client->update(array_filter([
                    'name'        => $formData['invoicing_company_name'] ?? $client->name,
                    'address'     => $formData['invoicing_address'] ?? $client->address,
                    'postal_code' => $formData['invoicing_postal_code'] ?? $client->postal_code,
                    'locality'    => $formData['invoicing_locality'] ?? $client->locality,
                    'email'       => $formData['invoicing_email'] ?? $client->email,
                ]));
            }
        }

        if (! $this->isGridLocked()) {
            $teamName = ($formData['company_name'] ?? null)
                ?: (($formData['school_name'] ?? null)
                ?: (($formData['contact_first_name'] ?? '').' '.($formData['contact_last_name'] ?? '')));

            $companyBloc = $formData['company_bloc'] ?? null;
            $defaultRun = match ($this->type) {
                'company' => RunRegistration::getCompanyRun(),
                'school'  => RunRegistration::getSchoolRun(),
                default   => null,
            };

            $cleanRows = $this->elements;
            $keptIds = [];

            foreach ($cleanRows as $elementData) {
                if (empty($elementData['first_name']) && empty($elementData['last_name'])) {
                    continue;
                }

                foreach ($elementData as $key => $val) {
                    if ($val === '') {
                        $elementData[$key] = null;
                    }
                }

                if ($defaultRun && empty($elementData['run_id'])) {
                    $elementData['run_id'] = $defaultRun->id;
                    $elementData['run_name'] = $defaultRun->name;
                    if ($this->type === 'company' && $companyBloc) {
                        $elementData['bloc'] = $companyBloc;
                    }
                } else {
                    $run = ! empty($elementData['run_id']) ? Run::find($elementData['run_id']) : null;
                    $elementData['run_name'] = $run ? $run->name : ($elementData['run_name'] ?? null);
                }

                $elementData['team'] = ! empty($elementData['team']) ? $elementData['team'] : $teamName;

                $elementId = $elementData['id'] ?? null;

                // Course clôturée : une ligne déjà enregistrée dessus reste intacte (elle est verrouillée dans la grille)
                if ($this->type === 'group' && in_array((int) ($elementData['run_id'] ?? 0), $this->closedGroupRunIds(), true) && $this->isPersistedOnSameRun(['id' => $elementId, 'run_id' => $elementData['run_id']])) {
                    $keptIds[] = $elementId;

                    continue;
                }

                unset($elementData['_k'], $elementData['_labels'], $elementData['_actions'], $elementData['id']);

                if ($elementId && $this->registration->runRegistrationElements()->where('id', $elementId)->exists()) {
                    $existingEl = $this->registration->runRegistrationElements()->find($elementId);
                    $existingEl->update($elementData);
                    $keptIds[] = $existingEl->id;
                } else {
                    $newEl = $this->registration->runRegistrationElements()->create($elementData);
                    $keptIds[] = $newEl->id;
                }
            }

            $this->registration->runRegistrationElements()->whereNotIn('id', $keptIds)->get()
                ->reject(fn ($el) => $this->type === 'group' && in_array((int) $el->run_id, $this->closedGroupRunIds(), true))
                ->each->delete();

            $this->loadElements();
        }

        if ($isNew) {
            $targetEmail = $this->registration->routeNotificationForMail();
            $mailSent = false;

            try {
                $this->registration->notify(new RunRegistrationLink);
                $mailSent = true;
            } catch (\Throwable $e) {
                report($e);
            }

            $successMessage = $mailSent && $targetEmail
                ? "Votre inscription a été créée et un email de confirmation contenant votre lien permanent d'édition vous a été envoyé à {$targetEmail}."
                : ($mailSent
                    ? "Votre inscription a été créée et un email de confirmation contenant votre lien permanent d'édition vous a été envoyé."
                    : "Votre inscription a été enregistrée avec succès. Attention : l'envoi de l'email de confirmation a rencontré un problème. Veuillez conserver précieusement le lien de cette page pour accéder à votre dossier.");

            return redirect()->to(URL::signedRoute('front.run-registration.edit', [
                'registration' => $this->registration->id,
            ]))->with('message', $successMessage);
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
