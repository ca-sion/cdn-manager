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
        })->get();

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

    public function getRunsForBirthdate(?string $birthdate, ?string $gender = null): array
    {
        $age = $this->calculateAge($birthdate);

        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', 'group')
                ->orWhereNull('available_for_types');
        })->get();

        $options = [];
        foreach ($runs as $r) {
            if ($age !== null && ! $r->matchesAge($age)) {
                continue;
            }

            if (! empty($gender) && ! $r->matchesGender($gender)) {
                continue;
            }

            $cost = $r->provision?->product?->price?->amount ?? $r->cost;
            $costLabel = ($cost > 0) ? number_format((float) $cost, 2, '.', '').' CHF' : 'Gratuit';

            $label = $r->name.' ('.$costLabel.')';
            if ($r->age_range_label) {
                $label .= ' — '.$r->age_range_label;
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

                // If current selected run is no longer valid for age + gender, reset it
                if ($runId !== '' && ! isset($availableRuns[$runId])) {
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
        if (! $this->isGridLocked() && isset($this->elements[$index])) {
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
        if (empty(trim($this->pasteTextData))) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($this->pasteTextData));
        $newRows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (str_contains($line, "\t")) {
                $parts = explode("\t", $line);
            } elseif (str_contains($line, '|')) {
                $parts = explode('|', $line);
            } elseif (str_contains($line, ';')) {
                $parts = explode(';', $line);
            } elseif (str_contains($line, ',')) {
                $parts = explode(',', $line);
            } else {
                $parts = preg_split('/\s+/', $line);
            }

            $parts = array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));

            if (count($parts) < 2) {
                continue;
            }

            $row = $this->emptyElement();
            $row['_k'] = 'l'.bin2hex(random_bytes(4));

            $row['first_name'] = $parts[0] ?? '';
            $row['last_name'] = $parts[1] ?? '';
            $row['birthdate'] = $parts[2] ?? '';

            if (isset($parts[3]) && in_array(strtoupper($parts[3]), ['M', 'F'])) {
                $row['gender'] = strtoupper($parts[3]);
            }

            if (isset($parts[4]) && str_contains($parts[4], '@')) {
                $row['email'] = $parts[4];
                if (isset($parts[5])) {
                    $row['nationality'] = strtoupper(substr($parts[5], 0, 3));
                }
            } elseif (isset($parts[4])) {
                $row['nationality'] = strtoupper(substr($parts[4], 0, 3));
            }

            $availableRuns = $this->type === 'group'
                ? $this->getRunsForBirthdate($row['birthdate'], $row['gender'] ?? 'M')
                : [];
            if (count($availableRuns) === 1) {
                $row['run_id'] = (string) array_key_first($availableRuns);
            }

            $newRows[] = $row;
        }

        if (! empty($newRows)) {
            $this->elements = array_values(array_filter($this->elements, function ($r) {
                return ! empty(trim($r['first_name'] ?? '')) ||
                       ! empty(trim($r['last_name'] ?? '')) ||
                       ! empty(trim($r['birthdate'] ?? '')) ||
                       ! empty(trim($r['email'] ?? ''));
            }));
            $this->elements = array_merge($this->elements, $newRows);
            $this->closeImportModal();
        }
    }

    /** Modèle Excel à remplir puis à réimporter (mêmes en-têtes que ceux attendus par l'import). */
    public function downloadImportTemplate()
    {
        $rows = collect([
            ['Prénom' => 'Lucas', 'Nom' => 'Rey', 'Date de naissance' => '02.02.2014', 'Genre' => 'M', 'Email' => 'lucas@example.com', 'Nationalité' => 'SUI'],
            ['Prénom' => 'Emma', 'Nom' => 'Dubois', 'Date de naissance' => '15.08.2012', 'Genre' => 'F', 'Email' => '', 'Nationalité' => 'SUI'],
        ]);

        return (new FastExcel($rows))->download('modele_import_participants.xlsx');
    }

    public function processExcelImport(): void
    {
        if (! $this->importFile) {
            return;
        }

        $this->validate([
            'importFile' => ['file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ], [
            'importFile.mimes' => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
            'importFile.max'   => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        $filePath = $this->importFile->getRealPath();
        try {
            $rawRows = (new FastExcel)->withoutHeaders()->import($filePath)
                ->map(fn ($r) => array_values(is_array($r) ? $r : $r->toArray()))
                ->all();
        } catch (\Throwable $e) {
            report($e);
            $this->addError('importFile', 'Impossible de lire ce fichier. Vérifiez qu\'il s\'agit bien d\'un fichier Excel valide.');

            return;
        }

        $newRows = [];
        foreach ($this->mapImportRows($rawRows) as $normalized) {
            $firstName = $normalized['first_name'];
            $lastName = $normalized['last_name'];

            if ($firstName === '' && $lastName === '') {
                continue;
            }

            $row = $this->emptyElement();
            $row['_k'] = 'l'.bin2hex(random_bytes(4));
            $row['first_name'] = $firstName;
            $row['last_name'] = $lastName;
            $row['birthdate'] = $normalized['birthdate'];
            $row['gender'] = $normalized['gender'];
            $row['email'] = $normalized['email'];
            $row['nationality'] = $normalized['nationality'] !== '' ? $normalized['nationality'] : 'SUI';

            $availableRuns = $this->type === 'group'
                ? $this->getRunsForBirthdate($row['birthdate'], $row['gender'] ?? 'M')
                : [];
            if (count($availableRuns) === 1) {
                $row['run_id'] = (string) array_key_first($availableRuns);
            }

            $newRows[] = $row;
        }

        if (! empty($newRows)) {
            $this->elements = array_values(array_filter($this->elements, function ($r) {
                return ! empty(trim($r['first_name'] ?? '')) ||
                       ! empty(trim($r['last_name'] ?? '')) ||
                       ! empty(trim($r['birthdate'] ?? '')) ||
                       ! empty(trim($r['email'] ?? ''));
            }));
            $this->elements = array_merge($this->elements, $newRows);
            $this->closeImportModal();
        } else {
            $this->addError('importFile', 'Aucun participant reconnu dans ce fichier. Vérifiez que les colonnes « Prénom » et « Nom » sont présentes.');
        }
    }

    /**
     * Transforme les lignes brutes d'un fichier Excel/CSV en participants normalisés.
     * Tolère : lignes de titre avant les en-têtes, en-têtes avec accents/parenthèses/variantes,
     * vraies dates Excel (objets date ou numéros de série), genres écrits en toutes lettres.
     * Sans en-têtes reconnus, l'ordre des colonnes est : Prénom, Nom, Naissance, Genre, E-mail, Nationalité.
     *
     * @param  array<int, array<int, mixed>>  $rawRows
     * @return array<int, array{first_name: string, last_name: string, birthdate: string, gender: string, email: string, nationality: string}>
     */
    protected function mapImportRows(array $rawRows): array
    {
        $aliases = [
            'first_name'  => ['prenom', 'first name', 'firstname', 'first_name', 'prenom eleve', 'prenom de l eleve'],
            'last_name'   => ['nom', 'last name', 'lastname', 'last_name', 'nom de famille', 'nom eleve', 'nom de l eleve'],
            'birthdate'   => ['date de naissance', 'date naissance', 'naissance', 'birthdate', 'date', 'ne le', 'nee le', 'annee de naissance', 'annee', 'date of birth', 'dob'],
            'gender'      => ['genre', 'sexe', 'gender', 'sex', 'm f', 'h f'],
            'email'       => ['email', 'e mail', 'mail', 'courriel', 'adresse email', 'adresse e mail', 'adresse mail'],
            'nationality' => ['nationalite', 'nationality', 'pays', 'country', 'nat'],
        ];

        $canon = function ($v): string {
            $v = $this->importCellToString($v);
            $v = preg_replace('/\(.*?\)/u', ' ', $v);
            $v = mb_strtolower(\Illuminate\Support\Str::ascii($v));

            return trim(preg_replace('/[^a-z0-9]+/', ' ', $v));
        };

        // Recherche de la ligne d'en-têtes (les 15 premières lignes) : doit contenir un prénom OU un nom
        $headerIndex = null;
        $map = [];
        foreach (array_slice($rawRows, 0, 15, true) as $i => $cells) {
            $found = [];
            foreach ($cells as $col => $cell) {
                $c = $canon($cell);
                if ($c === '') {
                    continue;
                }
                foreach ($aliases as $field => $names) {
                    if (! isset($found[$field]) && in_array($c, $names, true)) {
                        $found[$field] = $col;
                    }
                }
            }
            if (isset($found['first_name']) || isset($found['last_name'])) {
                $headerIndex = $i;
                $map = $found;
                break;
            }
        }

        if ($headerIndex === null) {
            $headerIndex = -1;
            $map = ['first_name' => 0, 'last_name' => 1, 'birthdate' => 2, 'gender' => 3, 'email' => 4, 'nationality' => 5];
        }

        $out = [];
        foreach ($rawRows as $i => $cells) {
            if ($i <= $headerIndex) {
                continue;
            }
            $get = fn (string $f) => isset($map[$f]) ? $this->importCellToString($cells[$map[$f]] ?? '') : '';

            $out[] = [
                'first_name'  => $get('first_name'),
                'last_name'   => $get('last_name'),
                'birthdate'   => $get('birthdate'),
                'gender'      => $this->importGender($get('gender')),
                'email'       => $get('email'),
                'nationality' => strtoupper(substr($get('nationality'), 0, 3)),
            ];
        }

        return $out;
    }

    protected function importCellToString(mixed $v): string
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->format('d.m.Y');
        }
        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            // Numéro de série Excel d'une date (ex. 43133 = 02.02.2018) ; une année seule (2018) reste telle quelle
            if ($v >= 10000 && $v <= 80000) {
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

        return in_array($v, ['f', 'fille', 'femme', 'feminin', 'female', 'w', 'madchen'], true) ? 'F' : 'M';
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

    public function isGridLocked(): bool
    {
        $deadline = setting('registrations_deadline');
        if (! $deadline) {
            return false;
        }

        // Le délai est saisi en heure suisse : sans fuseau explicite, il serait lu en UTC et la grille se verrouillerait 1 à 2 h trop tard
        return now()->greaterThan(Carbon::parse($deadline, 'Europe/Zurich'));
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

            $this->registration->runRegistrationElements()->whereNotIn('id', $keptIds)->delete();

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
