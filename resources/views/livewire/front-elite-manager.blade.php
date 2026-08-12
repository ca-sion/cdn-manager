<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-4">
    <!-- Header Banner & Top Controls (Compact & Slate Neutral) -->
    <div class="p-5 bg-slate-900 rounded-xl shadow-md text-white flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 bg-white/10 text-slate-200 rounded text-2xs font-semibold uppercase tracking-wider">
                    Espace Administration
                </span>
                <h1 class="text-xl font-bold tracking-tight text-white">
                    Gestion des coureurs Élite
                </h1>
            </div>
            <p class="mt-1 text-xs text-slate-300 max-w-2xl leading-normal">
                Supervision des athlètes Élite, suivi des contrats, primes, hébergements et envoi des accès.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <x-filament::button
                type="button"
                wire:click="exportExcel"
                color="gray"
                size="sm"
                icon="heroicon-m-arrow-down-tray"
            >
                Exporter (Excel / CSV)
            </x-filament::button>

            <x-filament::button
                type="button"
                wire:click="createRunner"
                color="primary"
                size="sm"
                icon="heroicon-m-plus"
            >
                Nouveau Coureur Élite
            </x-filament::button>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if (session()->has('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-lg text-xs font-medium flex items-center justify-between shadow-2xs">
            <span>✅ {{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-900 rounded-lg text-xs font-medium shadow-2xs">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <!-- Compact Statistics Dashboard Cards (Unified Slate Neutral Theme) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Athlètes Total</div>
            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stats['total'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hommes (M)</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $stats['total_men'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Femmes (F)</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $stats['total_women'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Primes Départ</div>
            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-0.5 font-mono">{{ number_format($stats['total_bonuses'], 0, '.', "'") }} CHF</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs col-span-2 sm:col-span-1">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hébergements</div>
            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stats['accommodations'] }} <span class="text-2xs font-normal text-gray-500">athlète(s)</span></div>
        </div>
    </div>

    <!-- Search & Filter Bar (Compact) -->
    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <div class="relative flex-1">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Rechercher par nom, prénom, nationalité, équipe, club ou e-mail..." 
                class="w-full pl-9 pr-3 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs focus:ring-1 focus:ring-slate-500 focus:border-slate-500 dark:text-white transition"
            >
            <span class="absolute left-3 top-2 text-gray-400 text-xs">🔍</span>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <!-- Filtre par Sexe / Genre -->
            <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                <span class="text-gray-500 font-medium">Sexe :</span>
                <select wire:model.live="genderFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                    <option value="">Tous les sexes</option>
                    <option value="M">Hommes (M)</option>
                    <option value="F">Femmes (F)</option>
                </select>
            </div>

            @if(!empty($search) || !empty($genderFilter))
                <button 
                    type="button"
                    wire:click="$set('search', ''); $set('genderFilter', '')" 
                    class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 font-medium text-xs rounded-lg transition"
                >
                    ✖ Réinitialiser
                </button>
            @endif
        </div>
    </div>

    <!-- Table of Elite Runners with Sortable Headers (Ultra Pro & Compact) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-900/80 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 select-none text-2xs">
                    <tr>
                        <!-- Nom / Prénom (Triage) -->
                        <th 
                            wire:click="sortBy('last_name')" 
                            class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800 transition group"
                        >
                            <div class="flex items-center gap-1">
                                <span>Athlète</span>
                                <span class="text-gray-400 group-hover:text-slate-700">
                                    @if($sortField === 'last_name')
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </div>
                        </th>

                        <!-- Genre / Sexe (Triage) -->
                        <th 
                            wire:click="sortBy('gender')" 
                            class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800 transition group"
                        >
                            <div class="flex items-center gap-1">
                                <span>Sexe / Naissance</span>
                                <span class="text-gray-400 group-hover:text-slate-700">
                                    @if($sortField === 'gender')
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </div>
                        </th>

                        <!-- Équipe / Club (Triage) -->
                        <th 
                            wire:click="sortBy('team')" 
                            class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800 transition group"
                        >
                            <div class="flex items-center gap-1">
                                <span>Équipe / Club</span>
                                <span class="text-gray-400 group-hover:text-slate-700">
                                    @if($sortField === 'team')
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </div>
                        </th>

                        <!-- Prime Départ (Triage) -->
                        <th 
                            wire:click="sortBy('bonus_start_amount')" 
                            class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800 transition group"
                        >
                            <div class="flex items-center gap-1">
                                <span>Prime Départ</span>
                                <span class="text-gray-400 group-hover:text-slate-700">
                                    @if($sortField === 'bonus_start_amount')
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </div>
                        </th>

                        <!-- Hébergement (Triage) -->
                        <th 
                            wire:click="sortBy('has_accommodation')" 
                            class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 dark:hover:bg-gray-800 transition group"
                        >
                            <div class="flex items-center gap-1">
                                <span>Hébergement</span>
                                <span class="text-gray-400 group-hover:text-slate-700">
                                    @if($sortField === 'has_accommodation')
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            </div>
                        </th>

                        <th class="px-3.5 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($runners as $runner)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition">
                            <td class="px-3.5 py-2.5">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $runner->last_name }} {{ $runner->first_name }}
                                </div>
                                <div class="text-2xs text-gray-500 font-mono flex items-center gap-1.5 mt-0.5">
                                    <span class="px-1 py-0.2 bg-gray-100 dark:bg-gray-700 rounded font-semibold text-gray-700 dark:text-gray-300">{{ $runner->nationality ?? 'SUI' }}</span>
                                    <span>{{ $runner->email ?? 'Sans email' }}</span>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 text-2xs font-semibold rounded bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        {{ (is_object($runner->gender) ? $runner->gender->value : $runner->gender) === 'F' ? 'Femme (F)' : 'Homme (M)' }}
                                    </span>
                                    <span class="text-2xs text-gray-500 font-mono">{{ $runner->birthdate?->format('d.m.Y') }}</span>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 font-medium text-gray-800 dark:text-gray-200">
                                {{ $runner->team ?: '-' }}
                            </td>
                            <td class="px-3.5 py-2.5 font-semibold font-mono text-slate-900 dark:text-white">
                                @if($runner->bonus_start_amount)
                                    {{ number_format($runner->bonus_start_amount, 2, '.', "'") }} CHF
                                @else
                                    <span class="text-gray-400 font-normal text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5">
                                @if($runner->has_accommodation)
                                    <span class="inline-flex items-center gap-1 text-2xs font-medium text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                        🏨 Oui ({{ $runner->accommodation_friday ? 'Ven ' : '' }}{{ $runner->accommodation_saturday ? 'Sam' : '' }})
                                    </span>
                                @else
                                    <span class="text-gray-400 text-2xs">Non</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-right">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    <button 
                                        wire:click="editRunner({{ $runner->id }})" 
                                        title="Éditer le dossier" 
                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white rounded text-2xs font-medium transition"
                                    >
                                        Éditer
                                    </button>

                                    @if($runner->run_registration_id)
                                        <a 
                                            href="{{ route('pdf.elite-contract', ['registration' => $runner->run_registration_id]) }}" 
                                            target="_blank" 
                                            title="Imprimer le contrat PDF" 
                                            class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-2xs font-medium border border-slate-200 dark:border-slate-600 transition"
                                        >
                                            PDF
                                        </a>

                                        <button 
                                            wire:click="sendEditLink({{ $runner->id }})" 
                                            wire:confirm="Envoyer le lien d'accès par e-mail à {{ $runner->email ?? $runner->runRegistration?->contact_email }} ?" 
                                            title="Envoyer l'accès par e-mail" 
                                            class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-2xs font-medium border border-slate-200 dark:border-slate-600 transition"
                                        >
                                            Lien
                                        </button>
                                    @endif

                                    <button 
                                        wire:click="deleteRunner({{ $runner->id }})" 
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer définitivement cet athlète Élite ?" 
                                        title="Supprimer" 
                                        class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900 dark:text-rose-300 rounded text-2xs font-medium border border-rose-200 dark:border-rose-800 transition"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 text-xs">
                                Aucun coureur Élite trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($runners->hasPages())
            <div class="p-3 border-t border-gray-100 dark:border-gray-700">
                {{ $runners->links() }}
            </div>
        @endif
    </div>

    <!-- Modal d'Édition / Création de Coureur Élite (Compact & Pro) -->
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-3xl w-full p-5 shadow-xl space-y-4 border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        {{ $isCreating ? '➕ Nouveau Coureur Élite' : '✏️ Fiche du Coureur Élite' }}
                    </h3>
                    <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 font-bold text-sm p-1">
                        ✖
                    </button>
                </div>

                <form wire:submit.prevent="saveRunner" class="space-y-4 text-xs">
                    <!-- SECTION 1: Identité du coureur -->
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                        <div class="text-2xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Identité & Course
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Prénom *</label>
                                <input type="text" wire:model="formData.first_name" required class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Nom *</label>
                                <input type="text" wire:model="formData.last_name" required class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Date de naissance *</label>
                                <input type="date" wire:model="formData.birthdate" required class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Genre *</label>
                                <select wire:model="formData.gender" required class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                                    <option value="M">Masculin (M)</option>
                                    <option value="F">Féminin (F)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Nationalité</label>
                                <select wire:model="formData.nationality" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                                    @foreach(\App\Helpers\CountryHelper::getOptions() as $code => $countryLabel)
                                        <option value="{{ $code }}">{{ $countryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Équipe / Club</label>
                                <input type="text" wire:model="formData.team" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Adresse E-mail</label>
                                <input type="email" wire:model="formData.email" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Course Élite *</label>
                                <select wire:model="formData.run_id" required class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                                    <option value="">Sélectionner une course...</option>
                                    @foreach($runs as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Coordonnées & IBAN -->
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                        <div class="text-2xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Coordonnées & IBAN
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Adresse</label>
                                <input type="text" wire:model="formData.address" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Complément adresse</label>
                                <input type="text" wire:model="formData.address_extension" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Code postal</label>
                                <input type="text" wire:model="formData.postal_code" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Localité</label>
                                <input type="text" wire:model="formData.locality" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Pays</label>
                                <select wire:model="formData.country" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white">
                                    @foreach(\App\Helpers\CountryHelper::getOptions() as $code => $countryLabel)
                                        <option value="{{ $code }}">{{ $countryLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">IBAN pour versement des primes</label>
                                <input type="text" wire:model="formData.iban" placeholder="CH.." class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs font-mono focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Primes et Contrat -->
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                        <div class="text-2xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Conditions, Primes & Contrat
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                            <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="formData.has_free_registration_fee" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                <span>Dossard offert</span>
                            </label>

                            <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model.live="formData.has_bonus_start" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                <span>Prime de départ accordée</span>
                            </label>

                            @if($formData['has_bonus_start'] ?? false)
                                <div>
                                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Montant Prime Départ (CHF)</label>
                                    <input type="number" step="0.01" wire:model="formData.bonus_start_amount" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs font-mono focus:ring-1 focus:ring-slate-500 dark:text-white">
                                </div>
                            @endif

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Prime Classement (CHF)</label>
                                <input type="number" step="0.01" wire:model="formData.bonus_ranking_amount" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs font-mono focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Prime Arrivée (CHF)</label>
                                <input type="number" step="0.01" wire:model="formData.bonus_arrival_amount" class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs font-mono focus:ring-1 focus:ring-slate-500 dark:text-white">
                            </div>

                            <div class="sm:col-span-3">
                                <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer mb-0.5">
                                    <input type="checkbox" wire:model.live="formData.has_expense_reimbursement" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                    <span>Remboursement des frais de déplacement</span>
                                </label>
                                @if($formData['has_expense_reimbursement'] ?? false)
                                    <textarea wire:model="formData.expense_reimbursement_precision" rows="2" placeholder="Précisions remboursement frais..." class="w-full mt-1 px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white"></textarea>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Hébergement -->
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                        <div class="text-2xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Prise en Charge Hébergement
                        </div>
                        <div class="space-y-2.5">
                            <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model.live="formData.has_accommodation" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                <span>Prise en charge de l'hébergement accordée</span>
                            </label>

                            @if($formData['has_accommodation'] ?? false)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                    <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" wire:model="formData.accommodation_friday" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                        <span>Nuitée du Vendredi</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" wire:model="formData.accommodation_saturday" class="rounded border-gray-300 text-slate-800 focus:ring-slate-500">
                                        <span>Nuitée du Samedi</span>
                                    </label>
                                    <div class="sm:col-span-2">
                                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Précisions sur l'hôtel / chambre</label>
                                        <textarea wire:model="formData.accommodation_precision" rows="2" placeholder="Type de chambre, réservation..." class="w-full px-2.5 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded text-xs focus:ring-1 focus:ring-slate-500 dark:text-white"></textarea>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 font-semibold text-xs rounded-lg transition">
                            Annuler
                        </button>
                        <x-filament::button
                            type="submit"
                            color="primary"
                            size="sm"
                            icon="heroicon-m-check"
                        >
                            Enregistrer l'athlète Élite
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
