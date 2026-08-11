<div class="space-y-8">
    @if (session()->has('message'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm" role="alert">
            <span class="font-semibold">Succès !</span> {{ session('message') }}
        </div>
    @endif

    {{-- SECTION 1: En-tête & Métriques de remplissage --}}
    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">Module d'inscription aux courses</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-indigo-100 text-indigo-800">
                        {{ match($type) {
                            'company' => 'Entreprise',
                            'school' => 'École',
                            'group' => 'Groupe',
                            'elite' => 'Élite',
                            default => ucfirst($type)
                        } }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Saisie rapide des données d'inscription et gestion des participants.</p>
            </div>

            @if ($deadline)
                <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-900 px-4 py-2 rounded-lg text-sm font-medium">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Délai d'inscription : <strong>{{ $deadline }}</strong></span>
                </div>
            @endif
        </div>

        {{-- Taux de remplissage des courses --}}
        <div>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Taux de remplissage des courses</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($runs as $run)
                    @php
                        $rate = $run->fill_rate;
                    @endphp
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between items-center text-sm font-medium">
                            <span class="text-gray-900">{{ $run->name }}</span>
                            <span class="text-xs text-gray-500">{{ $run->registrations_number ?? 0 }} / {{ $run->registrations_limit ?? '∞' }}</span>
                        </div>
                        @if ($run->registrations_limit)
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $rate) }}%"></div>
                            </div>
                            <div class="text-right text-xs text-indigo-700 font-semibold">{{ $rate }}% rempli</div>
                        @else
                            <div class="text-xs text-green-600 font-medium">Places illimitées</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic">Aucune course configurée pour ce type.</p>
                @endforelse
            </div>
        </div>
    </div>

    <form wire:submit.prevent="save" class="space-y-8">
        {{-- SECTION 2: Formulaire de données générales (En-tête) --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 space-y-6">
            <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Coordonnées générales & Facturation
            </h2>

            {{-- Champs spécifiques au Type --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if ($type === 'company')
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom de l'entreprise *</label>
                        <input type="text" wire:model="company_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('company_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                @elseif ($type === 'school')
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom de l'école *</label>
                        <input type="text" wire:model="school_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('school_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Niveau / Classe (ex: 3H, 4H...)</label>
                        <select wire:model="school_class_level" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Sélectionner</option>
                            @foreach (['3H','4H','5H','6H','7H','8H'] as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Responsable de classe - Prénom</label>
                        <input type="text" wire:model="school_class_holder_first_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Responsable de classe - Nom</label>
                        <input type="text" wire:model="school_class_holder_last_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email du responsable</label>
                        <input type="email" wire:model="school_class_holder_email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone du responsable</label>
                        <input type="text" wire:model="school_class_holder_phone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                @endif
            </div>

            {{-- Personne de contact générale --}}
            <div class="border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Personne de contact générale</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Prénom *</label>
                        <input type="text" wire:model="contact_first_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('contact_first_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Nom *</label>
                        <input type="text" wire:model="contact_last_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('contact_last_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email *</label>
                        <input type="email" wire:model="contact_email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('contact_email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Téléphone *</label>
                        <input type="text" wire:model="contact_phone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>

            {{-- Informations de facturation --}}
            <div class="border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Informations de facturation</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Raison sociale / Nom facturation</label>
                        <input type="text" wire:model="invoicing_company_name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Adresse</label>
                        <input type="text" wire:model="invoicing_address" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Complément d'adresse</label>
                        <input type="text" wire:model="invoicing_address_extension" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Code Postal</label>
                        <input type="text" wire:model="invoicing_postal_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Localité</label>
                        <input type="text" wire:model="invoicing_locality" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Email facturation</label>
                        <input type="email" wire:model="invoicing_email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: Grille de saisie des participants (LaraGrid) --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Participants à inscrire
                    </h2>
                    <p class="text-xs text-gray-500">Saisie style tableur Excel. Utilisez Tab et Entrée pour vous déplacer.</p>
                </div>
                @if ($isLocked)
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-800">
                        🔒 Grille verrouillée
                    </span>
                @else
                    <button type="button" wire:click="addRow" class="inline-flex items-center px-3 py-1.5 border border-indigo-200 text-xs font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Ajouter un participant
                    </button>
                @endif
            </div>

            @if ($isLocked)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
                    Le délai général d'inscription est dépassé. La liste des participants est actuellement verrouillée en lecture seule. Seules vos données de contact et facturation restent modifiables.
                </div>
            @endif

            {{-- Intégration LaraGrid Component --}}
            <div class="w-full overflow-x-auto">
                <x-laragrid :grid="$this->gridDefinition('elements')" :rows="$elements" />
            </div>
        </div>

        {{-- SECTION 4: Disclaimers & Mentions légales --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 text-xs text-gray-600 space-y-2">
            <h4 class="font-bold text-gray-800 uppercase tracking-wider text-xs">Informations importantes & Datasport</h4>
            <p>
                En soumettant ce formulaire, vous confirmez que l'ensemble des données personnelles saisies sont exactes. Ces informations seront transmises à notre partenaire de chronométrage <strong>Datasport AG</strong> pour la gestion des listes de départ, de la publication des résultats et du suivi de course.
            </p>
            <p>
                En poursuivant votre inscription, vous acceptez expressément les conditions générales et la politique de protection des données de Datasport.
            </p>
        </div>

        {{-- Actions de soumission --}}
        <div class="flex justify-end gap-4">
            <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Enregistrer l'inscription
            </button>
        </div>
    </form>
</div>