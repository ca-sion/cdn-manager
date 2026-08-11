<?php

namespace App\Livewire;

use App\Models\Run;
use Livewire\Component;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Notifications\RunRegistrationLink;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use LaraGrid\Grid;
use LaraGrid\Livewire\WithLaraGrid;
use LaraGrid\Columns\{SerialColumn, TextColumn, DateColumn, SelectColumn, CheckboxColumn, DecimalColumn};

class FrontRunRegistration extends Component
{
    use WithLaraGrid;

    public string $type = 'company';
    public $registration = null;

    // Company & School specific
    public $company_name;
    public $school_name;
    public $school_postal_code;
    public $school_locality;
    public $school_country = 'SUI';
    public $school_class_level;
    public $school_class_holder_first_name;
    public $school_class_holder_last_name;
    public $school_class_holder_email;
    public $school_class_holder_phone;

    // Contact person
    public $contact_first_name;
    public $contact_last_name;
    public $contact_email;
    public $contact_phone;

    // Invoicing & Payment
    public $invoicing_company_name;
    public $invoicing_address;
    public $invoicing_address_extension;
    public $invoicing_postal_code;
    public $invoicing_locality;
    public $invoicing_email;
    public $invoicing_note;
    public $payment_iban;
    public $payment_note;

    // Grid elements array for LaraGrid
    public array $elements = [];

    protected function rules(): array
    {
        $rules = [
            'contact_first_name' => 'required|string|max:255',
            'contact_last_name'  => 'required|string|max:255',
            'contact_email'      => 'required|email|max:255',
            'contact_phone'      => 'nullable|string|max:255',
        ];

        if ($this->type === 'company') {
            $rules['company_name'] = 'required|string|max:255';
        } elseif ($this->type === 'school') {
            $rules['school_name'] = 'required|string|max:255';
        }

        return $rules;
    }

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

            $this->fill($this->registration->toArray());

            $registrationType = $this->registration->run_registration_type?->value ?? $this->registration->type;
            if ($registrationType) {
                $type = is_object($registrationType) ? $registrationType->value : (string) $registrationType;
            }
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
        ];

        if ($this->type === 'group') {
            $columns = array_merge($columns, [
                TextColumn::make('nationality')->label('Nationalité')->width(100),
                TextColumn::make('email')->label('Email')->grow(),
                TextColumn::make('team')->label('Club')->grow(),
                SelectColumn::make('run_id')->label('Course')->options($runOptions)->grow(),
                CheckboxColumn::make('with_video')->label('Vidéo')->width(80),
            ]);
        }

        if ($this->type === 'company') {
            $columns = array_merge($columns, [
                TextColumn::make('nationality')->label('Nationalité')->width(100),
                TextColumn::make('email')->label('Email')->grow(),
                TextColumn::make('bloc')->label('Bloc')->width(100),
                CheckboxColumn::make('with_video')->label('Vidéo')->width(80),
                TextColumn::make('voucher_code')->label('Voucher')->width(120),
            ]);
        }

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

        $grid = Grid::make('elements')
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
        $this->validate();

        $isNew = ! $this->registration || ! $this->registration->exists;

        if ($isNew) {
            $this->registration = new RunRegistration();
        }

        $this->registration->fill([
            'run_registration_type'          => $this->type,
            'company_name'                   => $this->company_name,
            'school_name'                    => $this->school_name,
            'school_postal_code'             => $this->school_postal_code,
            'school_locality'                => $this->school_locality,
            'school_country'                 => $this->school_country,
            'school_class_level'             => $this->school_class_level,
            'school_class_holder_first_name' => $this->school_class_holder_first_name,
            'school_class_holder_last_name'  => $this->school_class_holder_last_name,
            'school_class_holder_email'      => $this->school_class_holder_email,
            'school_class_holder_phone'      => $this->school_class_holder_phone,
            'contact_first_name'             => $this->contact_first_name,
            'contact_last_name'              => $this->contact_last_name,
            'contact_email'                  => $this->contact_email,
            'contact_phone'                  => $this->contact_phone,
            'invoicing_company_name'         => $this->invoicing_company_name,
            'invoicing_address'              => $this->invoicing_address,
            'invoicing_address_extension'    => $this->invoicing_address_extension,
            'invoicing_postal_code'          => $this->invoicing_postal_code,
            'invoicing_locality'             => $this->invoicing_locality,
            'invoicing_email'                => $this->invoicing_email,
            'invoicing_note'                 => $this->invoicing_note,
            'payment_iban'                   => $this->payment_iban,
            'payment_note'                   => $this->payment_note,
        ]);

        $this->registration->save();

        if (! $this->isGridLocked()) {
            $teamName = $this->company_name ?: ($this->school_name ?: ($this->contact_first_name . ' ' . $this->contact_last_name));

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

    public function render()
    {
        $runs = Run::where(function ($query) {
            $query->whereJsonContains('available_for_types', $this->type)
                ->orWhereNull('available_for_types');
        })->get();

        $deadline = setting('registrations_deadline');

        return view('livewire.front-run-registration', [
            'runs'     => $runs,
            'deadline' => $deadline ? Carbon::parse($deadline)->format('d.m.Y à H:i') : null,
            'isLocked' => $this->isGridLocked(),
        ])->layout('layouts.app');
    }
}
