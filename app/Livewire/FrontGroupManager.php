<?php

namespace App\Livewire;

use Exception;
use App\Models\Client;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Notifications\RunRegistrationLink;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\URL;
use Rap2hpoutre\FastExcel\FastExcel;

class FrontGroupManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $invoiceFilter = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public bool $showLinkClientModal = false;
    public ?int $selectedRegistrationId = null;
    public ?int $selectedClientId = null;

    protected $queryString = ['search', 'typeFilter', 'invoiceFilter', 'sortField', 'sortDirection'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInvoiceFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function sendEditLink(int $registrationId): void
    {
        $registration = RunRegistration::findOrFail($registrationId);

        $targetEmail = $registration->routeNotificationForMail();
        if (! $targetEmail) {
            session()->flash('error', 'Aucune adresse email de contact disponible pour ce dossier.');
            return;
        }

        try {
            $registration->notify(new RunRegistrationLink());
            session()->flash('message', "Lien d'accès permanent envoyé avec succès à {$targetEmail} !");
        } catch (Exception $e) {
            session()->flash('error', "Erreur lors de l'envoi de l'e-mail : " . $e->getMessage());
        }
    }

    public function openLinkClientModal(int $registrationId): void
    {
        $this->selectedRegistrationId = $registrationId;
        $registration = RunRegistration::findOrFail($registrationId);
        $this->selectedClientId = $registration->client_id;
        $this->showLinkClientModal = true;
    }

    public function saveClientLink(): void
    {
        if (! $this->selectedRegistrationId) {
            return;
        }

        $registration = RunRegistration::findOrFail($this->selectedRegistrationId);
        $registration->client_id = $this->selectedClientId ?: null;

        if ($this->selectedClientId) {
            $client = Client::find($this->selectedClientId);
            if ($client) {
                $registration->invoicing_company_name = $client->name ?: $client->invoicing_name;
                $registration->invoicing_address = $client->address;
                $registration->invoicing_postal_code = $client->postal_code;
                $registration->invoicing_locality = $client->locality;
                $registration->invoicing_email = $client->email ?: $client->invoicing_email;
            }
        }

        $registration->save();
        $this->showLinkClientModal = false;
        session()->flash('message', 'Association du client mise à jour avec succès.');
    }

    public function deleteRegistration(int $id): void
    {
        $registration = RunRegistration::findOrFail($id);
        $registration->runRegistrationElements()->delete();
        $registration->delete();

        session()->flash('message', 'Dossier d\'inscription et ses participants supprimés.');
    }

    public function exportDatasportSchool()
    {
        $registrations = RunRegistration::where('run_registration_type', 'school')
            ->with('runRegistrationElements.run')
            ->get();
        return \App\Filament\Resources\RunRegistrationResource::generateDatasportSchoolExcel($registrations);
    }

    public function exportDatasportCompany()
    {
        $registrations = RunRegistration::where('run_registration_type', 'company')
            ->with('runRegistrationElements.run')
            ->get();
        return \App\Filament\Resources\RunRegistrationResource::generateDatasportCompanyExcel($registrations);
    }

    public function exportDatasportGroup()
    {
        $registrations = RunRegistration::where('run_registration_type', 'group')
            ->with('runRegistrationElements.run')
            ->get();
        return \App\Filament\Resources\RunRegistrationResource::generateDatasportGroupExcel($registrations);
    }

    public function exportAggregatedData()
    {
        $registrations = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['runRegistrationElements.run', 'client'])
            ->get();
        return \App\Filament\Resources\RunRegistrationResource::generateAggregatedExcel($registrations);
    }

    public function render()
    {
        $query = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['client', 'runRegistrationElements.run']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('run_registrations.company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('run_registrations.school_name', 'like', '%' . $this->search . '%')
                  ->orWhere('run_registrations.contact_first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('run_registrations.contact_last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('run_registrations.contact_email', 'like', '%' . $this->search . '%')
                  ->orWhere('run_registrations.school_locality', 'like', '%' . $this->search . '%');
            });
        }

        if (! empty($this->typeFilter)) {
            $query->where('run_registrations.run_registration_type', $this->typeFilter);
        }

        if ($this->invoiceFilter === 'linked') {
            $query->whereNotNull('run_registrations.client_id');
        } elseif ($this->invoiceFilter === 'unlinked') {
            $query->whereNull('run_registrations.client_id');
        }

        $allowedSorts = [
            'company_name'          => 'run_registrations.company_name',
            'school_name'           => 'run_registrations.school_name',
            'contact_last_name'     => 'run_registrations.contact_last_name',
            'run_registration_type' => 'run_registrations.run_registration_type',
            'created_at'            => 'run_registrations.created_at',
            'id'                    => 'run_registrations.id',
        ];

        $sortColumn = $allowedSorts[$this->sortField] ?? 'run_registrations.created_at';
        $sortDirection = in_array(strtolower($this->sortDirection), ['asc', 'desc']) ? strtolower($this->sortDirection) : 'desc';

        $registrations = $query->orderBy($sortColumn, $sortDirection)->paginate(20);

        // Calculate global statistics separated by type using transversal RunRegistration model helpers
        $allRegistrations = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['runRegistrationElements.run.provision.product.price'])
            ->get();

        $companies = $allRegistrations->filter(fn($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'company');
        $schools   = $allRegistrations->filter(fn($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'school');
        $groups    = $allRegistrations->filter(fn($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'group');

        $stats = [
            'total_dossiers'          => $allRegistrations->count(),
            'total_participants'      => $allRegistrations->sum(fn($r) => $r->participants_count),

            'companies_dossiers'      => $companies->count(),
            'companies_participants'  => $companies->sum(fn($r) => $r->participants_count),

            'schools_dossiers'        => $schools->count(),
            'schools_participants'    => $schools->sum(fn($r) => $r->participants_count),

            'groups_dossiers'         => $groups->count(),
            'groups_participants'     => $groups->sum(fn($r) => $r->participants_count),

            'filtered_estimated'      => $registrations->getCollection()->sum(fn($r) => $r->estimated_total),
            'total_estimated'         => $allRegistrations->sum(fn($r) => $r->estimated_total),
        ];

        $clients = Client::orderBy('name')->get();

        return view('livewire.front-group-manager', [
            'registrations' => $registrations,
            'stats'         => $stats,
            'clients'       => $clients,
        ])->layout('layouts.app', ['title' => 'Gestion des Inscriptions Groupes & Entreprises']);
    }
}
