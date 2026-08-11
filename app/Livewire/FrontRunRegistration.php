<?php

namespace App\Livewire;

use App\Models\Run;
use Livewire\Component;
use App\Models\RunRegistration;
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

    // Grid elements array for LaraGrid
    public array $elements = [];

    public function mount($type = null, $registration = null): void
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

            $this->form->fill($registrationData);
        } else {
            $this->form->fill();
        }

        $this->type = in_array($type, ['company', 'school', 'group', 'elite']) ? $type : 'company';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations sur la course & Remplissage')
                    ->icon('heroicon-m-flag')
                    ->collapsible()
                    ->schema([
                        Placeholder::make('metrics')
                            ->label('')
                            ->content(function (FrontRunRegistration $livewire) {
                                $runs = Run::where(function ($query) use ($livewire) {
                                    $query->whereJsonContains('available_for_types', $livewire->type)
                                        ->orWhereNull('available_for_types');
                                })->get();

                                $deadline = setting('registrations_deadline');

                                return view('livewire.front-run-registration-metrics', [
                                    'runs'     => $runs,
                                    'deadline' => $deadline ? Carbon::parse($deadline)->format('d.m.Y à H:i') : null,
                                    'isLocked' => $livewire->isGridLocked(),
                                ]);
                            }),
                    ]),

                Section::make('Coordonnées de l\'organisation')
                    ->icon('heroicon-m-building-office')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nom de l\'entreprise')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'company')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'company')
                            ->columnSpanFull(),

                        TextInput::make('school_name')
                            ->label('Nom de l\'école / établissement')
                            ->required(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school')
                            ->columnSpanFull(),

                        TextInput::make('school_postal_code')
                            ->label('Code postal école')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),

                        TextInput::make('school_locality')
                            ->label('Localité école')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),

                        Select::make('school_class_level')
                            ->label('Degré / Classe')
                            ->options([
                                '3H' => '3H',
                                '4H' => '4H',
                                '5H' => '5H',
                                '6H' => '6H',
                                '7H' => '7H',
                                '8H' => '8H',
                            ])
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),

                        TextInput::make('school_class_holder_first_name')
                            ->label('Prénom du titulaire de classe')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),

                        TextInput::make('school_class_holder_last_name')
                            ->label('Nom du titulaire de classe')
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),

                        TextInput::make('school_class_holder_email')
                            ->label('Email du titulaire de classe')
                            ->email()
                            ->visible(fn (FrontRunRegistration $livewire) => $livewire->type === 'school'),
                    ]),

                Section::make('Personne responsable / Contact')
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
                            ->label('Adresse email du responsable')
                            ->email()
                            ->required(),

                        TextInput::make('contact_phone')
                            ->label('Numéro de téléphone')
                            ->tel(),
                    ]),

                Section::make('Facturation & Règlement')
                    ->icon('heroicon-m-credit-card')
                    ->columns(2)
                    ->collapsible()
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

                        TextInput::make('payment_iban')
                            ->label('IBAN de remboursement (pour Élite/Groupes)')
                            ->visible(fn (FrontRunRegistration $livewire) => in_array($livewire->type, ['elite', 'group', 'company']))
                            ->columnSpanFull(),

                        Textarea::make('invoicing_note')
                            ->label('Remarque facturation')
                            ->columnSpanFull(),
                    ]),

                Section::make('Participants à inscrire (Grille interactive)')
                    ->icon('heroicon-m-table-cells')
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
            $runOptions[(string) $r->id] = $r->name . ' (' . ($r->cost ? $r->cost . ' CHF' : 'Gratuit') . ')';
        }

        $columns = [
            SerialColumn::make(),
            TextColumn::make('first_name')->label('Prénom')->grow(),
            TextColumn::make('last_name')->label('Nom')->grow(),
            DateColumn::make('birthdate')->label('Date de naissance')->width(130),
            SelectColumn::make('gender')->label('Sexe')->options(['M' => 'M', 'F' => 'F'])->width(80),
            TextColumn::make('nationality')->label('Nationalité')->width(100),
            TextColumn::make('email')->label('Email')->grow(),
            SelectColumn::make('run_id')->label('Course')->options($runOptions)->grow(),
            TextColumn::make('bloc')->label('Bloc')->width(100),
            CheckboxColumn::make('with_video')->label('Vidéo')->width(80),
            TextColumn::make('voucher_code')->label('Voucher')->width(120),
        ];

        if ($this->type === 'elite') {
            $columns = array_merge($columns, [
                TextColumn::make('address')->label('Adresse'),
                TextColumn::make('postal_code')->label('N° Postal'),
                TextColumn::make('locality')->label('Localité'),
                TextColumn::make('country')->label('Pays'),
                TextColumn::make('iban')->label('IBAN'),
                CheckboxColumn::make('has_free_registration_fee')->label('Frais offerts'),
                CheckboxColumn::make('has_bonus_start')->label('Prime départ'),
                DecimalColumn::make('bonus_start_amount')->label('Mt départ'),
                DecimalColumn::make('bonus_ranking_amount')->label('Mt classement'),
                DecimalColumn::make('bonus_arrival_amount')->label('Mt arrivée'),
                CheckboxColumn::make('has_accommodation')->label('Hébergement'),
                CheckboxColumn::make('accommodation_friday')->label('Ven.'),
                CheckboxColumn::make('accommodation_saturday')->label('Sam.'),
                TextColumn::make('accommodation_precision')->label('Précisions hébergement'),
                CheckboxColumn::make('has_expense_reimbursement')->label('Remb. frais'),
                TextColumn::make('expense_reimbursement_precision')->label('Précisions frais'),
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
            $teamName = ($formData['company_name'] ?? null)
                ?: (($formData['school_name'] ?? null)
                ?: (($formData['contact_first_name'] ?? '') . ' ' . ($formData['contact_last_name'] ?? '')));

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

            // Soft-delete only the elements that were removed from the grid by the user
            $this->registration->runRegistrationElements()->whereNotIn('id', $keptIds)->delete();
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
