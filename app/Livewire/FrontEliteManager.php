<?php

namespace App\Livewire;

use Exception;
use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Notifications\EliteRunnerLink;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Rap2hpoutre\FastExcel\FastExcel;

class FrontEliteManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $genderFilter = '';
    public string $sortField = 'last_name';
    public string $sortDirection = 'asc';
    public bool $showEditModal = false;
    public bool $isCreating = false;
    public ?int $editingId = null;

    // Form fields
    public array $formData = [];

    protected $queryString = ['search', 'genderFilter', 'sortField', 'sortDirection'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGenderFilter(): void
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

    public function rules(): array
    {
        return [
            'formData.first_name'                 => 'required|string|max:255',
            'formData.last_name'                  => 'required|string|max:255',
            'formData.birthdate'                  => 'required|date',
            'formData.gender'                     => 'required|in:M,F',
            'formData.nationality'                => 'nullable|string|max:255',
            'formData.email'                      => 'nullable|email|max:255',
            'formData.team'                       => 'nullable|string|max:255',
            'formData.run_id'                     => 'required|exists:runs,id',
            'formData.address'                    => 'nullable|string|max:255',
            'formData.address_extension'          => 'nullable|string|max:255',
            'formData.postal_code'                => 'nullable|string|max:50',
            'formData.locality'                   => 'nullable|string|max:255',
            'formData.country'                    => 'nullable|string|max:255',
            'formData.iban'                       => 'nullable|string|max:255',
            'formData.has_free_registration_fee'  => 'boolean',
            'formData.has_bonus_start'            => 'boolean',
            'formData.bonus_start_amount'         => 'nullable|numeric|min:0',
            'formData.bonus_ranking_amount'       => 'nullable|numeric|min:0',
            'formData.bonus_arrival_amount'       => 'nullable|numeric|min:0',
            'formData.has_accommodation'          => 'boolean',
            'formData.accommodation_friday'       => 'boolean',
            'formData.accommodation_saturday'     => 'boolean',
            'formData.accommodation_precision'    => 'nullable|string',
            'formData.has_expense_reimbursement'  => 'boolean',
            'formData.expense_reimbursement_precision' => 'nullable|string',
        ];
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->formData = [
            'first_name'                       => '',
            'last_name'                        => '',
            'birthdate'                        => '',
            'gender'                           => 'M',
            'nationality'                      => 'SUI',
            'email'                            => '',
            'team'                             => '',
            'run_id'                           => '',
            'address'                          => '',
            'address_extension'                => '',
            'postal_code'                      => '',
            'locality'                         => '',
            'country'                          => 'SUI',
            'iban'                             => '',
            'has_free_registration_fee'        => true,
            'has_bonus_start'                  => false,
            'bonus_start_amount'               => null,
            'bonus_ranking_amount'             => null,
            'bonus_arrival_amount'             => null,
            'has_accommodation'                => false,
            'accommodation_friday'             => false,
            'accommodation_saturday'           => false,
            'accommodation_precision'          => '',
            'has_expense_reimbursement'        => false,
            'expense_reimbursement_precision'  => '',
        ];
    }

    public function createRunner(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->isCreating = true;
        $this->showEditModal = true;
    }

    public function editRunner(int $id): void
    {
        $element = RunRegistrationElement::findOrFail($id);
        $this->editingId = $element->id;
        $this->isCreating = false;

        $this->formData = [
            'first_name'                       => $element->first_name,
            'last_name'                        => $element->last_name,
            'birthdate'                        => $element->birthdate?->format('Y-m-d'),
            'gender'                           => is_object($element->gender) ? $element->gender->value : $element->gender,
            'nationality'                      => $element->nationality,
            'email'                            => $element->email,
            'team'                             => $element->team,
            'run_id'                           => $element->run_id,
            'address'                          => $element->address,
            'address_extension'                => $element->address_extension,
            'postal_code'                      => $element->postal_code,
            'locality'                         => $element->locality,
            'country'                          => $element->country ?? 'SUI',
            'iban'                             => $element->iban ?: $element->runRegistration?->payment_iban,
            'has_free_registration_fee'        => (bool) $element->has_free_registration_fee,
            'has_bonus_start'                  => (bool) $element->has_bonus_start,
            'bonus_start_amount'               => $element->bonus_start_amount,
            'bonus_ranking_amount'             => $element->bonus_ranking_amount,
            'bonus_arrival_amount'             => $element->bonus_arrival_amount,
            'has_accommodation'                => (bool) $element->has_accommodation,
            'accommodation_friday'             => (bool) $element->accommodation_friday,
            'accommodation_saturday'           => (bool) $element->accommodation_saturday,
            'accommodation_precision'          => $element->accommodation_precision,
            'has_expense_reimbursement'        => (bool) $element->has_expense_reimbursement,
            'expense_reimbursement_precision'  => $element->expense_reimbursement_precision,
        ];

        $this->showEditModal = true;
    }

    public function saveRunner(): void
    {
        $this->validate();

        $run = Run::find($this->formData['run_id']);

        if ($this->isCreating) {
            $registration = RunRegistration::create([
                'run_registration_type' => 'elite',
                'contact_first_name'    => $this->formData['first_name'],
                'contact_last_name'     => $this->formData['last_name'],
                'contact_email'         => $this->formData['email'],
                'payment_iban'          => $this->formData['iban'],
            ]);

            $registration->runRegistrationElements()->create([
                'first_name'                       => $this->formData['first_name'],
                'last_name'                        => $this->formData['last_name'],
                'birthdate'                        => $this->formData['birthdate'],
                'gender'                           => $this->formData['gender'],
                'nationality'                      => $this->formData['nationality'],
                'email'                            => $this->formData['email'],
                'team'                             => $this->formData['team'],
                'run_id'                           => $this->formData['run_id'],
                'run_name'                         => $run?->name,
                'address'                          => $this->formData['address'],
                'address_extension'                => $this->formData['address_extension'],
                'postal_code'                      => $this->formData['postal_code'],
                'locality'                         => $this->formData['locality'],
                'country'                          => $this->formData['country'],
                'iban'                             => $this->formData['iban'],
                'has_free_registration_fee'        => $this->formData['has_free_registration_fee'],
                'has_bonus_start'                  => $this->formData['has_bonus_start'],
                'bonus_start_amount'               => $this->formData['bonus_start_amount'],
                'bonus_ranking_amount'             => $this->formData['bonus_ranking_amount'],
                'bonus_arrival_amount'             => $this->formData['bonus_arrival_amount'],
                'has_accommodation'                => $this->formData['has_accommodation'],
                'accommodation_friday'             => $this->formData['accommodation_friday'],
                'accommodation_saturday'           => $this->formData['accommodation_saturday'],
                'accommodation_precision'          => $this->formData['accommodation_precision'],
                'has_expense_reimbursement'        => $this->formData['has_expense_reimbursement'],
                'expense_reimbursement_precision'  => $this->formData['expense_reimbursement_precision'],
            ]);

            session()->flash('message', 'Coureur Élite créé avec succès !');
        } else {
            $element = RunRegistrationElement::findOrFail($this->editingId);
            $element->update([
                'first_name'                       => $this->formData['first_name'],
                'last_name'                        => $this->formData['last_name'],
                'birthdate'                        => $this->formData['birthdate'],
                'gender'                           => $this->formData['gender'],
                'nationality'                      => $this->formData['nationality'],
                'email'                            => $this->formData['email'],
                'team'                             => $this->formData['team'],
                'run_id'                           => $this->formData['run_id'],
                'run_name'                         => $run?->name,
                'address'                          => $this->formData['address'],
                'address_extension'                => $this->formData['address_extension'],
                'postal_code'                      => $this->formData['postal_code'],
                'locality'                         => $this->formData['locality'],
                'country'                          => $this->formData['country'],
                'iban'                             => $this->formData['iban'],
                'has_free_registration_fee'        => $this->formData['has_free_registration_fee'],
                'has_bonus_start'                  => $this->formData['has_bonus_start'],
                'bonus_start_amount'               => $this->formData['bonus_start_amount'],
                'bonus_ranking_amount'             => $this->formData['bonus_ranking_amount'],
                'bonus_arrival_amount'             => $this->formData['bonus_arrival_amount'],
                'has_accommodation'                => $this->formData['has_accommodation'],
                'accommodation_friday'             => $this->formData['accommodation_friday'],
                'accommodation_saturday'           => $this->formData['accommodation_saturday'],
                'accommodation_precision'          => $this->formData['accommodation_precision'],
                'has_expense_reimbursement'        => $this->formData['has_expense_reimbursement'],
                'expense_reimbursement_precision'  => $this->formData['expense_reimbursement_precision'],
            ]);

            if ($element->runRegistration) {
                $element->runRegistration->update([
                    'contact_first_name' => $this->formData['first_name'],
                    'contact_last_name'  => $this->formData['last_name'],
                    'contact_email'      => $this->formData['email'],
                    'payment_iban'       => $this->formData['iban'],
                ]);
            }

            session()->flash('message', 'Fiche du coureur Élite mise à jour !');
        }

        $this->showEditModal = false;
    }

    public function sendEditLink(int $elementId): void
    {
        $element = RunRegistrationElement::findOrFail($elementId);

        $targetEmail = $element->email ?: $element->runRegistration?->contact_email;
        if (! $targetEmail) {
            session()->flash('error', 'Aucune adresse email disponible pour ce coureur.');
            return;
        }

        $signedUrl = URL::signedRoute('front.run-registration.edit', [
            'registration' => $element->run_registration_id,
        ]);

        try {
            $element->runRegistration->notify(new EliteRunnerLink($element, $signedUrl));
            session()->flash('message', "E-mail d'accès envoyé avec succès à {$targetEmail} !");
        } catch (Exception $e) {
            session()->flash('error', "Erreur lors de l'envoi : " . $e->getMessage());
        }
    }

    public function deleteRunner(int $id): void
    {
        $element = RunRegistrationElement::findOrFail($id);
        $registration = $element->runRegistration;

        $element->delete();
        if ($registration && $registration->runRegistrationElements()->count() === 0) {
            $registration->delete();
        }

        session()->flash('message', 'Coureur Élite supprimé.');
    }

    public function exportExcel()
    {
        $elements = RunRegistrationElement::whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', 'elite'))->get();

        if ($elements->isEmpty()) {
            session()->flash('error', 'Aucun coureur à exporter.');
            return null;
        }

        $data = $elements->map(fn ($el) => [
            'ID Coureur'          => $el->id,
            'Nom'                 => $el->last_name,
            'Prénom'              => $el->first_name,
            'Date Naissance'      => $el->birthdate?->format('d.m.Y'),
            'Genre'               => is_object($el->gender) ? $el->gender->value : $el->gender,
            'Nationalité'         => $el->nationality,
            'Équipe / Club'       => $el->team,
            'Email'               => $el->email,
            'Course'              => $el->run?->name ?? $el->run_name,
            'IBAN'                => $el->iban ?: $el->runRegistration?->payment_iban,
            'Prime départ (CHF)'  => $el->bonus_start_amount,
            'Prime classement'    => $el->bonus_ranking_amount,
            'Hébergement'         => $el->has_accommodation ? 'Oui' : 'Non',
            'Nuitée Vendredi'     => $el->accommodation_friday ? 'Oui' : 'Non',
            'Nuitée Samedi'       => $el->accommodation_saturday ? 'Oui' : 'Non',
            'Précisions héb.'     => $el->accommodation_precision,
            'Defraiement frais'   => $el->has_expense_reimbursement ? 'Oui' : 'Non',
        ]);

        return (new FastExcel($data))->download('export_coureurs_elite_' . date('Ymd_His') . '.xlsx');
    }

    public function render()
    {
        $query = RunRegistrationElement::whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', 'elite'))
            ->with(['run', 'runRegistration']);

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('nationality', 'like', '%' . $this->search . '%')
                  ->orWhere('team', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if (! empty($this->genderFilter)) {
            $query->where('gender', $this->genderFilter);
        }

        $allowedSorts = ['last_name', 'first_name', 'gender', 'birthdate', 'nationality', 'team', 'bonus_start_amount', 'has_accommodation'];
        $sortField = in_array($this->sortField, $allowedSorts) ? $this->sortField : 'last_name';
        $sortDirection = in_array(strtolower($this->sortDirection), ['asc', 'desc']) ? strtolower($this->sortDirection) : 'asc';

        $runners = $query->orderBy($sortField, $sortDirection)->paginate(25);

        $runs = Run::where(function ($q) {
            $q->whereJsonContains('available_for_types', 'elite')
              ->orWhereNull('available_for_types');
        })->get();

        $baseStatsQuery = RunRegistrationElement::whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', 'elite'));

        $stats = [
            'total'          => (clone $baseStatsQuery)->count(),
            'total_men'      => (clone $baseStatsQuery)->where('gender', 'M')->count(),
            'total_women'    => (clone $baseStatsQuery)->where('gender', 'F')->count(),
            'total_bonuses'  => (clone $baseStatsQuery)->sum('bonus_start_amount'),
            'accommodations' => (clone $baseStatsQuery)->where('has_accommodation', true)->count(),
        ];

        return view('livewire.front-elite-manager', [
            'runners' => $runners,
            'runs'    => $runs,
            'stats'   => $stats,
        ])->layout('layouts.app', ['title' => 'Gestion des Coureurs Élite']);
    }
}
