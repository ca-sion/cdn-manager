<?php

namespace App\Livewire;

use Exception;
use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RunRegistration;
use App\Notifications\RunRegistrationLink;
use App\Filament\Resources\RunRegistrationResource;

class FrontGroupManager extends Component
{
    use WithPagination;

    public string $activeTab = 'school';

    public string $search = '';

    public string $degreeFilter = '';

    public string $conformityFilter = '';

    public string $invoiceFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showLinkClientModal = false;

    public ?int $selectedRegistrationId = null;

    public ?int $selectedClientId = null;

    protected $queryString = [
        'activeTab'        => ['except' => 'school'],
        'search'           => ['except' => ''],
        'degreeFilter'     => ['except' => ''],
        'conformityFilter' => ['except' => ''],
        'invoiceFilter'    => ['except' => ''],
        'sortField'        => ['except' => 'created_at'],
        'sortDirection'    => ['except' => 'desc'],
    ];

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['school', 'company', 'group', 'all']) ? $tab : 'school';
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDegreeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedConformityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInvoiceFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->degreeFilter = '';
        $this->conformityFilter = '';
        $this->invoiceFilter = '';
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
            $registration->notify(new RunRegistrationLink);
            session()->flash('message', "Lien d'accès permanent envoyé avec succès à {$targetEmail} !");
        } catch (Exception $e) {
            session()->flash('error', "Erreur lors de l'envoi de l'e-mail : ".$e->getMessage());
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

        return RunRegistrationResource::generateDatasportSchoolExcel($registrations);
    }

    public function exportDatasportCompany()
    {
        $registrations = RunRegistration::where('run_registration_type', 'company')
            ->with('runRegistrationElements.run')
            ->get();

        return RunRegistrationResource::generateDatasportCompanyExcel($registrations);
    }

    public function exportDatasportGroup()
    {
        $registrations = RunRegistration::where('run_registration_type', 'group')
            ->with('runRegistrationElements.run')
            ->get();

        return RunRegistrationResource::generateDatasportGroupExcel($registrations);
    }

    public function exportAggregatedData()
    {
        $registrations = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['runRegistrationElements.run.provision.product', 'client', 'invoice'])
            ->get();

        return RunRegistrationResource::generateAggregatedExcel($registrations);
    }

    public function exportDetailedParticipants()
    {
        $registrations = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['runRegistrationElements.run.provision.product', 'client', 'invoice'])
            ->get();

        return RunRegistrationResource::generateDetailedParticipantsExcel($registrations);
    }

    public function render()
    {
        $minStudents = (int) config('cdn.interclasses.min_students', 8);
        $minGirls = (int) config('cdn.interclasses.min_girls', 3);

        $query = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['client', 'school', 'runRegistrationElements.run.provision.product']);

        if ($this->activeTab !== 'all') {
            $query->where('run_registrations.run_registration_type', $this->activeTab);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('run_registrations.company_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.school_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.contact_first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.contact_last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.school_class_holder_first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.school_class_holder_last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.contact_email', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.school_locality', 'like', '%'.$this->search.'%')
                    ->orWhere('run_registrations.invoicing_locality', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->degreeFilter) && ($this->activeTab === 'school' || $this->activeTab === 'all')) {
            $query->where('run_registrations.school_class_level', $this->degreeFilter);
        }

        if ($this->invoiceFilter === 'linked') {
            $query->whereNotNull('run_registrations.client_id');
        } elseif ($this->invoiceFilter === 'unlinked') {
            $query->whereNull('run_registrations.client_id');
        }

        if ($this->activeTab === 'school' && ! empty($this->conformityFilter)) {
            if ($this->conformityFilter === 'conform') {
                $query->has('runRegistrationElements', '>=', $minStudents)
                    ->whereHas('runRegistrationElements', fn ($q) => $q->where('gender', 'F'), '>=', $minGirls);
            } elseif ($this->conformityFilter === 'non_conform') {
                $query->where(function ($q) use ($minStudents, $minGirls) {
                    $q->has('runRegistrationElements', '<', $minStudents)
                        ->orWhereHas('runRegistrationElements', fn ($sq) => $sq->where('gender', 'F'), '<', $minGirls);
                });
            }
        }

        $allowedSorts = [
            'company_name'          => 'run_registrations.company_name',
            'school_name'           => 'run_registrations.school_name',
            'school_class_level'    => 'run_registrations.school_class_level',
            'contact_last_name'     => 'run_registrations.contact_last_name',
            'run_registration_type' => 'run_registrations.run_registration_type',
            'created_at'            => 'run_registrations.created_at',
            'id'                    => 'run_registrations.id',
        ];

        $sortColumn = $allowedSorts[$this->sortField] ?? 'run_registrations.created_at';
        $sortDirection = in_array(strtolower($this->sortDirection), ['asc', 'desc']) ? strtolower($this->sortDirection) : 'desc';

        $filteredCollection = (clone $query)->get();
        $registrations = $query->orderBy($sortColumn, $sortDirection)->paginate(20);

        // Global dataset for tab badges & aggregated statistics
        $allRegistrations = RunRegistration::where('run_registration_type', '!=', 'elite')
            ->with(['runRegistrationElements.run.provision.product', 'client', 'school'])
            ->get();

        $companies = $allRegistrations->filter(fn ($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'company');
        $schools = $allRegistrations->filter(fn ($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'school');
        $groups = $allRegistrations->filter(fn ($r) => (is_object($r->run_registration_type) ? $r->run_registration_type->value : (string) $r->run_registration_type) === 'group');

        $schoolsConformCount = $schools->filter(fn ($r) => $r->isSchoolTeamConform())->count();
        $schoolsIncompleteCount = $schools->count() - $schoolsConformCount;

        $degreesCount = [];
        foreach (['3H', '4H', '5H', '6H', '7H', '8H'] as $deg) {
            $degreesCount[$deg] = $schools->filter(fn ($r) => (string) $r->school_class_level === $deg)->count();
        }

        $hasActiveFilters = ! empty($this->search)
            || ! empty($this->degreeFilter)
            || ! empty($this->conformityFilter)
            || ! empty($this->invoiceFilter);

        $stats = [
            'total_dossiers'     => $allRegistrations->count(),
            'total_participants' => $allRegistrations->sum(fn ($r) => $r->participants_count),
            'total_girls'        => $allRegistrations->sum(fn ($r) => $r->girls_count),
            'total_boys'         => $allRegistrations->sum(fn ($r) => $r->boys_count),
            'total_estimated'    => $allRegistrations->sum(fn ($r) => $r->estimated_total),

            'companies_dossiers'     => $companies->count(),
            'companies_participants' => $companies->sum(fn ($r) => $r->participants_count),
            'companies_girls'        => $companies->sum(fn ($r) => $r->girls_count),
            'companies_boys'         => $companies->sum(fn ($r) => $r->boys_count),
            'companies_estimated'    => $companies->sum(fn ($r) => $r->estimated_total),
            'companies_linked'       => $companies->filter(fn ($r) => ! empty($r->client_id))->count(),

            'schools_dossiers'         => $schools->count(),
            'schools_participants'     => $schools->sum(fn ($r) => $r->participants_count),
            'schools_girls'            => $schools->sum(fn ($r) => $r->girls_count),
            'schools_boys'             => $schools->sum(fn ($r) => $r->boys_count),
            'schools_conform_count'    => $schoolsConformCount,
            'schools_incomplete_count' => $schoolsIncompleteCount,
            'schools_degrees_count'    => $degreesCount,

            'groups_dossiers'     => $groups->count(),
            'groups_participants' => $groups->sum(fn ($r) => $r->participants_count),
            'groups_girls'        => $groups->sum(fn ($r) => $r->girls_count),
            'groups_boys'         => $groups->sum(fn ($r) => $r->boys_count),
            'groups_estimated'    => $groups->sum(fn ($r) => $r->estimated_total),

            'has_active_filters'    => $hasActiveFilters,
            'filtered_dossiers'     => $filteredCollection->count(),
            'filtered_participants' => $filteredCollection->sum(fn ($r) => $r->participants_count),
            'filtered_girls'        => $filteredCollection->sum(fn ($r) => $r->girls_count),
            'filtered_boys'         => $filteredCollection->sum(fn ($r) => $r->boys_count),
            'filtered_estimated'    => $filteredCollection->sum(fn ($r) => $r->estimated_total),
        ];

        $clients = Client::orderBy('name')->get();

        return view('livewire.front-group-manager', [
            'registrations' => $registrations,
            'stats'         => $stats,
            'clients'       => $clients,
            'minStudents'   => $minStudents,
            'minGirls'      => $minGirls,
        ])->layout('layouts.app', ['title' => 'Gestion des Inscriptions Groupes & Entreprises']);
    }
}
