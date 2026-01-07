<?php

namespace App\Livewire;

use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Enums\RunRegistrationTypesEnum;
use Livewire\Component;
use Illuminate\Support\Facades\URL;

class FrontRunRegistration extends Component
{
    public $type;
    public ?RunRegistration $registration = null;
    
    // Données de base
    public $company_name;
    public $school_name;
    public $contact_first_name;
    public $contact_last_name;
    public $contact_email;
    public $contact_phone;
    
    // Données de facturation
    public $invoicing_company_name;
    public $invoicing_address;
    public $invoicing_postal_code;
    public $invoicing_locality;
    
    // Grille des participants
    public $elements = [];

    protected $rules = [
        'contact_first_name' => 'required',
        'contact_last_name' => 'required',
        'contact_email' => 'required|email',
        'elements.*.first_name' => 'nullable',
        'elements.*.last_name' => 'nullable',
    ];

    public function mount($type, $registration = null)
    {
        $this->type = $type;
        if ($registration) {
            $this->registration = is_numeric($registration) ? RunRegistration::findOrFail($registration) : $registration;
            $this->fill($this->registration->toArray());
            $this->elements = $this->registration->runRegistrationElements->toArray();
        }

        // Assurer qu'il y a toujours au moins 10 lignes dans la grille
        while (count($this->elements) < 10) {
            $this->elements[] = $this->emptyElement();
        }
    }

    private function emptyElement()
    {
        return [
            'first_name' => '',
            'last_name' => '',
            'birthdate' => '',
            'gender' => '',
            'run_id' => '',
            'team' => '',
        ];
    }

    public function addRow()
    {
        $this->elements[] = $this->emptyElement();
    }

    public function save()
    {
        $this->validate();

        if ($this->isGridLocked() && $this->registration) {
            // Si verrouillé, on ne touche pas aux éléments
            $this->registration->fill([
                'company_name' => $this->company_name,
                'school_name' => $this->school_name,
                'contact_first_name' => $this->contact_first_name,
                'contact_last_name' => $this->contact_last_name,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
            ]);
            $this->registration->save();
            session()->flash('message', 'Coordonnées mises à jour (la liste des participants est verrouillée).');
            return;
        }

        if (!$this->registration) {
            $this->registration = new RunRegistration();
        }

        $this->registration->fill([
            'type' => $this->type,
            'company_name' => $this->company_name,
            'school_name' => $this->school_name,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'invoicing_company_name' => $this->invoicing_company_name,
            'invoicing_address' => $this->invoicing_address,
            'invoicing_postal_code' => $this->invoicing_postal_code,
            'invoicing_locality' => $this->invoicing_locality,
        ]);

        $this->registration->save();

        // Enregistrement des éléments (filtrer les lignes vides)
        $this->registration->runRegistrationElements()->delete();
        foreach ($this->elements as $elementData) {
            if (!empty($elementData['first_name']) || !empty($elementData['last_name'])) {
                $this->registration->runRegistrationElements()->create($elementData);
            }
        }

        if (request()->routeIs('front.run-registration.create')) {
            return redirect()->to(URL::signedRoute('front.run-registration.edit', [
                'type' => $this->type,
                'registration' => $this->registration->id
            ]));
        }

        session()->flash('message', 'Inscription enregistrée avec succès.');
    }

    public function isGridLocked()
    {
        // On suppose que setting() est disponible (Outerweb Settings)
        $deadline = setting('registrations_deadline');
        if (! $deadline) {
            return false;
        }

        return now()->greaterThan(\Illuminate\Support\Carbon::parse($deadline));
    }

    public function render()
    {
        return view('livewire.front-run-registration', [
            'runs' => Run::whereJsonContains('available_for_types', $this->type)->get(),
            'isLocked' => $this->isGridLocked()
        ])->layout('layouts.app');
    }
}
