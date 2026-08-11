<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Formulaire d'inscription aux courses</h1>
            <p class="text-sm text-gray-500 mt-1">Saisie des données de contact, facturation et participants.</p>
        </div>
        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
            Type : {{ match($type) {
                'company' => 'Entreprise',
                'school'  => 'École',
                'group'   => 'Groupe',
                'elite'   => 'Élite',
                default   => ucfirst($type)
            } }}
        </span>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-4 pt-4">
            <x-filament::button
                type="submit"
                size="lg"
                color="primary"
                icon="heroicon-m-check-circle"
            >
                Enregistrer l'inscription
            </x-filament::button>
        </div>
    </form>
</div>