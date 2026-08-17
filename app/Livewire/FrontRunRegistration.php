<?php

namespace App\Livewire;

use App\Models\Run;
use App\Models\Client;
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
            $this->form->fill($registrationData);
        } else {
            $initialData = [
                'school_country' => 'SUI',
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

        $elementsList = $this->registration
            ? $this->registration->runRegistrationElements()->withTrashed()->get()
            : collect();

        if ($elementsList->isNotEmpty()) {
            $this->elements = $elementsList->map(function ($el) {
                $arr = array_merge($this->emptyElement(), $el->toArray());
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
                        TextInput::make('school_name')
                            ->label('Nom du centre scolaire')
                            ->placeholder('Ex: Centre scolaire Pasteur')
                            ->required()
                            ->columnSpan(2),

                        Select::make('school_class_level')
                            ->label('Degré scolaire')
                            ->options(SchoolClassLevel::class)
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('school_postal_code')
                            ->label('Code postal')
                            ->placeholder('1950')
                            ->numeric()
                            ->required(),

                        TextInput::make('school_locality')
                            ->label('Localité')
                            ->placeholder('Sion')
                            ->required(),

                        Select::make('school_country')
                            ->label('Pays')
                            ->options(CountryHelper::getOptions())
                            ->searchable()
                            ->default('SUI')
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
                            ->label('E-mail du titulaire')
                            ->email()
                            ->required(),

                        TextInput::make('school_class_holder_phone')
                            ->label('N° de téléphone portable')
                            ->tel()
                            ->placeholder('079 123 45 67'),
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
                } elseif (preg_match('/^(\d{4})$/', $birthdate, $m)) {
                    $year = (int) $m[1];
                    $validDate = ($year >= 1900 && $year <= (int) now()->format('Y') + 1);
                } elseif (preg_match('/^(\d{2})$/', $birthdate, $m)) {
                    $validDate = true;
                }

                if (! $validDate) {
                    $rowErrors[] = 'Date de naissance invalide (ex: jj.mm.aaaa ou aaaa)';
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

    public function getRunsForBirthdate(?string $birthdate): array
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

    public function updatedElements(): void
    {
        if (! is_array($this->elements)) {
            return;
        }

        foreach ($this->elements as &$row) {
            $runId = trim((string) ($row['run_id'] ?? ''));
            $birthdate = trim((string) ($row['birthdate'] ?? ''));

            if ($runId !== '' && $birthdate !== '') {
                $age = $this->calculateAge($birthdate);
                if ($age !== null) {
                    $selectedRun = Run::find((int) $runId);
                    if ($selectedRun && ! $selectedRun->matchesAge($age)) {
                        $row['run_id'] = '';
                    }
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

    public function removeRow(int $index): void
    {
        if (! $this->isGridLocked() && isset($this->elements[$index])) {
            array_splice($this->elements, $index, 1);
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

            $availableRuns = $this->getRunsForBirthdate($row['birthdate']);
            if (! empty($availableRuns) && count($availableRuns) === 1) {
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

    public function processExcelImport(): void
    {
        if (! $this->importFile) {
            return;
        }

        $filePath = $this->importFile->getRealPath();
        $rows = (new FastExcel)->import($filePath);

        $newRows = [];
        foreach ($rows as $item) {
            $normalized = [];
            foreach ($item as $k => $v) {
                $cleanKey = mb_strtolower(trim((string) $k));
                $normalized[$cleanKey] = trim((string) $v);
            }

            $firstName = $normalized['prenom'] ?? $normalized['prénom'] ?? $normalized['first_name'] ?? $normalized['first name'] ?? '';
            $lastName = $normalized['nom'] ?? $normalized['last_name'] ?? $normalized['last name'] ?? '';

            if (empty($firstName) && empty($lastName)) {
                continue;
            }

            $row = $this->emptyElement();
            $row['_k'] = 'l'.bin2hex(random_bytes(4));
            $row['first_name'] = $firstName;
            $row['last_name'] = $lastName;
            $row['birthdate'] = $normalized['date_de_naissance'] ?? $normalized['birthdate'] ?? $normalized['date de naissance'] ?? $normalized['date'] ?? '';

            $gender = strtoupper($normalized['genre'] ?? $normalized['gender'] ?? $normalized['sexe'] ?? 'M');
            $row['gender'] = in_array($gender, ['M', 'F']) ? $gender : 'M';

            $row['email'] = $normalized['email'] ?? $normalized['courriel'] ?? '';
            $row['nationality'] = strtoupper(substr($normalized['nationalite'] ?? $normalized['nationalité'] ?? $normalized['country'] ?? 'SUI', 0, 3));

            $availableRuns = $this->getRunsForBirthdate($row['birthdate']);
            if (! empty($availableRuns) && count($availableRuns) === 1) {
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

        return now()->greaterThan(Carbon::parse($deadline));
    }

    public function save()
    {
        $errors = $this->verifyIntegrity();
        if (count($errors) > 0) {
            session()->flash('message', '⚠️ Impossible d\'enregistrer : '.count($errors).' participant(s) contiennent des anomalies. Veuillez consulter le rapport de vérification ci-dessous.');

            return;
        }

        $formData = $this->form->getState();
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
                unset($elementData['_k'], $elementData['id']);

                if ($elementId && $this->registration->runRegistrationElements()->withTrashed()->where('id', $elementId)->exists()) {
                    $existingEl = $this->registration->runRegistrationElements()->withTrashed()->find($elementId);
                    if ($existingEl->trashed()) {
                        $existingEl->restore();
                    }
                    $existingEl->update($elementData);
                    $keptIds[] = $existingEl->id;
                } else {
                    $newEl = $this->registration->runRegistrationElements()->create($elementData);
                    $keptIds[] = $newEl->id;
                }
            }

            $this->registration->runRegistrationElements()->whereNotIn('id', $keptIds)->delete();
        }

        if ($isNew) {
            try {
                $this->registration->notify(new RunRegistrationLink);
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
