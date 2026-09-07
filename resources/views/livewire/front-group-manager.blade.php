<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-4">
    <!-- Header Banner & Top Controls -->
    <div class="p-5 bg-slate-900 rounded-xl shadow-md text-white flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 bg-white/10 text-slate-200 rounded text-2xs font-semibold uppercase tracking-wider">
                    Espace Responsable
                </span>
                <h1 class="text-xl font-bold tracking-tight text-white">
                    Supervision des inscriptions par lot
                </h1>
            </div>
            <p class="mt-1 text-xs text-slate-300 max-w-2xl leading-normal">
                Gestion dédiée des classes d'écoles (Interclasses 3H-8H), des entreprises et des clubs/groupes.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Bouton Export Contextuel selon l'onglet actif -->
            @if($activeTab === 'school')
                <button wire:click="exportDatasportSchool" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-100 font-semibold text-xs rounded-lg transition border border-slate-700 shadow-xs cursor-pointer">
                    📥 Export Datasport interclasses
                </button>
            @elseif($activeTab === 'company')
                <button wire:click="exportDatasportCompany" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-100 font-semibold text-xs rounded-lg transition border border-slate-700 shadow-xs cursor-pointer">
                    📥 Export Datasport entreprises
                </button>
            @elseif($activeTab === 'group')
                <button wire:click="exportDatasportGroup" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-100 font-semibold text-xs rounded-lg transition border border-slate-700 shadow-xs cursor-pointer">
                    📥 Export Datasport groupes
                </button>
            @endif

            <!-- Menu Déroulant Exportations Complètes -->
            <div x-data="{ open: false }" class="relative inline-block text-left">
                <button 
                    @click="open = !open" 
                    @click.away="open = false" 
                    type="button" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-100 font-semibold text-xs rounded-lg transition border border-slate-700 shadow-xs cursor-pointer"
                >
                    <span>Tous les exports</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div 
                    x-show="open" 
                    x-transition 
                    class="absolute right-0 mt-1.5 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50 py-1 divide-y divide-gray-100 dark:divide-gray-700"
                >
                    <div class="py-1">
                        <button wire:click="exportDatasportSchool" @click="open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 cursor-pointer">
                            🏫 Export Datasport interclasses
                        </button>
                        <button wire:click="exportDatasportCompany" @click="open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 cursor-pointer">
                            🏢 Export Datasport entreprises
                        </button>
                        <button wire:click="exportDatasportGroup" @click="open = false" class="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 cursor-pointer">
                            🏃 Export Datasport groupes / clubs
                        </button>
                    </div>
                    <div class="py-1">
                        <button wire:click="exportAggregatedData" @click="open = false" class="w-full text-left px-3 py-2 text-xs text-slate-900 dark:text-white font-semibold hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 cursor-pointer">
                            📊 Export agrégé (Tous types)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bouton Création Nouveau Dossier -->
            @php
                $targetType = match($activeTab) {
                    'school'  => 'school',
                    'group'   => 'group',
                    default   => 'company'
                };
                $btnLabel = match($activeTab) {
                    'school'  => 'Nouvelle classe',
                    'group'   => 'Nouveau groupe',
                    default   => 'Nouvelle entreprise'
                };
            @endphp
            <x-filament::button
                tag="a"
                href="{{ route('front.run-registration.create', ['type' => $targetType]) }}" 
                color="primary"
                size="sm"
                icon="heroicon-m-plus"
            >
                {{ $btnLabel }}
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

    <!-- Barre d'Onglets de Navigation (Tabs) -->
    <div class="flex border-b border-gray-200 dark:border-gray-700 gap-1 bg-white dark:bg-gray-800/60 p-1.5 rounded-xl border shadow-2xs">
        <button 
            type="button" 
            wire:click="setTab('school')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'school' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}"
        >
            <span>🏫 Interclasses (Écoles)</span>
            <span class="px-2 py-0.5 rounded-full text-2xs font-extrabold {{ $activeTab === 'school' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $stats['schools_dossiers'] }}
            </span>
        </button>

        <button 
            type="button" 
            wire:click="setTab('company')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'company' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}"
        >
            <span>🏢 Entreprises</span>
            <span class="px-2 py-0.5 rounded-full text-2xs font-extrabold {{ $activeTab === 'company' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $stats['companies_dossiers'] }}
            </span>
        </button>

        <button 
            type="button" 
            wire:click="setTab('group')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'group' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}"
        >
            <span>🏃 Groupes & Clubs</span>
            <span class="px-2 py-0.5 rounded-full text-2xs font-extrabold {{ $activeTab === 'group' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $stats['groups_dossiers'] }}
            </span>
        </button>

        <button 
            type="button" 
            wire:click="setTab('all')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold transition cursor-pointer {{ $activeTab === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}"
        >
            <span>🌐 Vue globale</span>
            <span class="px-2 py-0.5 rounded-full text-2xs font-extrabold {{ $activeTab === 'all' ? 'bg-white/20 text-white dark:bg-slate-900/20 dark:text-slate-900' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $stats['total_dossiers'] }}
            </span>
        </button>
    </div>

    <!-- Contextual Summary Statistics Cards -->
    @if($activeTab === 'school')
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Classes inscrites</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['schools_dossiers'] }} <span class="text-xs font-normal text-gray-500">classes</span>
                </div>
                <div class="text-2xs text-gray-500 mt-0.5">3H à 8H</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Total élèves</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['schools_participants'] }} <span class="text-xs font-normal text-gray-500">élèves</span>
                </div>
                <div class="text-2xs font-semibold text-slate-600 dark:text-slate-400 mt-0.5">
                    <span class="text-pink-600 dark:text-pink-400">{{ $stats['schools_girls'] }} Filles</span> / 
                    <span class="text-sky-600 dark:text-sky-400">{{ $stats['schools_boys'] }} Garçons</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Conformité règlement</div>
                <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $stats['schools_conform_count'] }} <span class="text-xs font-normal text-gray-500">conformes</span>
                </div>
                <div class="text-2xs font-medium text-amber-600 dark:text-amber-400 mt-0.5">
                    {{ $stats['schools_incomplete_count'] }} classe(s) incomplète(s)
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Répartition par degré</div>
                <div class="flex flex-wrap gap-1 mt-1.5">
                    @foreach(['3H', '4H', '5H', '6H', '7H', '8H'] as $deg)
                        <span class="px-1.5 py-0.5 rounded text-2xs font-mono font-semibold {{ ($stats['schools_degrees_count'][$deg] ?? 0) > 0 ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500' }}">
                            {{ $deg }}: {{ $stats['schools_degrees_count'][$deg] ?? 0 }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif($activeTab === 'company')
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Entreprises</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['companies_dossiers'] }} <span class="text-xs font-normal text-gray-500">équipes</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Coureurs inscrits</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['companies_participants'] }} <span class="text-xs font-normal text-gray-500">coureurs</span>
                </div>
                <div class="text-2xs font-semibold text-slate-600 dark:text-slate-400 mt-0.5">
                    {{ $stats['companies_girls'] }} F / {{ $stats['companies_boys'] }} M
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Facturation estimée</div>
                <div class="text-xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ number_format($stats['companies_estimated'], 2, '.', "'") }} <span class="text-xs font-sans text-gray-500">CHF</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Clients liés</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['companies_linked'] }} / {{ $stats['companies_dossiers'] }}
                </div>
                <div class="text-2xs text-gray-500 mt-0.5">Prêts pour facturation</div>
            </div>
        </div>
    @elseif($activeTab === 'group')
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Groupes & Clubs</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['groups_dossiers'] }} <span class="text-xs font-normal text-gray-500">groupes</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Athlètes inscrits</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">
                    {{ $stats['groups_participants'] }} <span class="text-xs font-normal text-gray-500">athlètes</span>
                </div>
                <div class="text-2xs font-semibold text-slate-600 dark:text-slate-400 mt-0.5">
                    {{ $stats['groups_girls'] }} F / {{ $stats['groups_boys'] }} M
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Montant estimé</div>
                <div class="text-xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                    {{ number_format($stats['groups_estimated'], 2, '.', "'") }} <span class="text-xs font-sans text-gray-500">CHF</span>
                </div>
            </div>
        </div>
    @else
        <!-- Vue Globale (All) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Écoles / Classes</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $stats['schools_dossiers'] }} <span class="text-2xs text-gray-400 font-normal">classes</span></div>
                <div class="text-2xs text-gray-500">{{ $stats['schools_participants'] }} élèves ({{ $stats['schools_girls'] }} F / {{ $stats['schools_boys'] }} M)</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Entreprises</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $stats['companies_dossiers'] }} <span class="text-2xs text-gray-400 font-normal">sociétés</span></div>
                <div class="text-2xs text-gray-500">{{ $stats['companies_participants'] }} coureurs ({{ $stats['companies_girls'] }} F / {{ $stats['companies_boys'] }} M)</div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
                <div class="text-2xs font-bold text-gray-500 uppercase tracking-wider">Groupes / Clubs</div>
                <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">{{ $stats['groups_dossiers'] }} <span class="text-2xs text-gray-400 font-normal">groupes</span></div>
                <div class="text-2xs text-gray-500">{{ $stats['groups_participants'] }} athlètes ({{ $stats['groups_girls'] }} F / {{ $stats['groups_boys'] }} M)</div>
            </div>

            <div class="bg-slate-900 text-white p-3.5 rounded-xl border border-slate-800 shadow-2xs">
                <div class="text-2xs font-bold text-slate-300 uppercase tracking-wider">Total Général</div>
                <div class="text-xl font-extrabold text-white mt-1">{{ $stats['total_participants'] }} <span class="text-2xs text-slate-300 font-normal">participants</span></div>
                <div class="text-2xs text-slate-300">{{ $stats['total_dossiers'] }} dossiers ({{ $stats['total_girls'] }} F / {{ $stats['total_boys'] }} M)</div>
            </div>
        </div>
    @endif

    <!-- Dynamic Filtered Summary Banner -->
    @if($stats['has_active_filters'])
        <div class="p-3.5 bg-slate-800 text-white rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 border border-slate-700 shadow-xs">
            <div class="flex items-center gap-2.5 flex-wrap text-xs">
                <span class="px-2 py-0.5 bg-amber-400/20 text-amber-300 font-bold uppercase tracking-wider rounded text-2xs border border-amber-400/30">
                    ⚡ Sélection filtrée
                </span>
                <span class="font-medium text-slate-200">
                    <strong class="text-white">{{ $stats['filtered_dossiers'] }}</strong> dossier(s) • 
                    <strong class="text-white">{{ $stats['filtered_participants'] }}</strong> participant(s) 
                    (<span class="text-pink-300 font-semibold">{{ $stats['filtered_girls'] }} F</span> / <span class="text-sky-300 font-semibold">{{ $stats['filtered_boys'] }} M</span>)
                    @if($stats['filtered_estimated'] > 0)
                        • Montant : <strong class="text-emerald-400 font-mono">{{ number_format($stats['filtered_estimated'], 2, '.', "'") }} CHF</strong>
                    @endif
                </span>
            </div>
            <button 
                type="button" 
                wire:click="resetFilters" 
                class="text-2xs text-slate-400 hover:text-white underline cursor-pointer self-end sm:self-auto"
            >
                Effacer les filtres
            </button>
        </div>
    @endif

    <!-- Search & Filters Bar (Adaptative) -->
    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <div class="relative flex-1">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ $activeTab === 'school' ? 'Rechercher par établissement, enseignant, localité, e-mail...' : 'Rechercher par nom, responsable, localité, e-mail...' }}" 
                class="w-full pl-9 pr-3 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs focus:ring-1 focus:ring-slate-500 focus:border-slate-500 dark:text-white transition"
            >
            <span class="absolute left-3 top-2 text-gray-400 text-xs">🔍</span>
        </div>

        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            @if($activeTab === 'school' || $activeTab === 'all')
                <!-- Filtre par Degré scolaire -->
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                    <span class="text-gray-500 font-medium">Degré :</span>
                    <select wire:model.live="degreeFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                        <option value="">Tous</option>
                        @foreach(\App\Enums\SchoolClassLevel::cases() as $level)
                            <option value="{{ $level->value }}">{{ $level->getLabel() }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($activeTab === 'school')
                <!-- Filtre par Conformité Règlement -->
                <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                    <span class="text-gray-500 font-medium">Règlement :</span>
                    <select wire:model.live="conformityFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                        <option value="">Tous statuts</option>
                        <option value="conform">✅ Conformes (≥{{ $minStudents }} él., ≥{{ $minGirls }} F)</option>
                        <option value="non_conform">⚠️ Incomplètes</option>
                    </select>
                </div>
            @endif

            <!-- Filtre par Client / Facturation -->
            <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                <span class="text-gray-500 font-medium">Facturation :</span>
                <select wire:model.live="invoiceFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                    <option value="">Tous</option>
                    <option value="linked">Client Lié</option>
                    <option value="unlinked">Non Lié</option>
                </select>
            </div>

            @if(!empty($search) || !empty($degreeFilter) || !empty($conformityFilter) || !empty($invoiceFilter))
                <button 
                    type="button"
                    wire:click="resetFilters" 
                    class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 font-medium text-xs rounded-lg transition cursor-pointer"
                >
                    ✖ Réinitialiser
                </button>
            @endif
        </div>
    </div>

    <!-- Table of Registrations -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-900/80 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 select-none text-2xs">
                    <tr>
                        @if($activeTab === 'school')
                            <th wire:click="sortBy('school_name')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Établissement scolaire
                            </th>
                            <th wire:click="sortBy('school_class_level')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Degré
                            </th>
                            <th class="px-3.5 py-2.5">
                                Titulaire
                            </th>
                            <th class="px-3.5 py-2.5">
                                Élèves
                            </th>
                            <th class="px-3.5 py-2.5">
                                Statut
                            </th>
                            <th class="px-3.5 py-2.5">
                                Facturation
                            </th>
                        @elseif($activeTab === 'company')
                            <th wire:click="sortBy('company_name')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Entreprise / Équipe
                            </th>
                            <th class="px-3.5 py-2.5">
                                Bloc de départ
                            </th>
                            <th class="px-3.5 py-2.5">
                                Responsable contact
                            </th>
                            <th class="px-3.5 py-2.5">
                                Coureurs
                            </th>
                            <th class="px-3.5 py-2.5">
                                Montant
                            </th>
                            <th class="px-3.5 py-2.5">
                                Client
                            </th>
                        @elseif($activeTab === 'group')
                            <th wire:click="sortBy('company_name')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Groupe / Club
                            </th>
                            <th class="px-3.5 py-2.5">
                                Responsable contact
                            </th>
                            <th class="px-3.5 py-2.5">
                                Participants
                            </th>
                            <th class="px-3.5 py-2.5">
                                Montant
                            </th>
                            <th class="px-3.5 py-2.5">
                                Client
                            </th>
                        @else
                            <!-- Vue Globale (All) -->
                            <th wire:click="sortBy('company_name')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Dossier / Société / Établissement
                            </th>
                            <th wire:click="sortBy('run_registration_type')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Type
                            </th>
                            <th wire:click="sortBy('school_class_level')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                                Degré
                            </th>
                            <th class="px-3.5 py-2.5">
                                Responsable contact
                            </th>
                            <th class="px-3.5 py-2.5">
                                Part.
                            </th>
                            <th class="px-3.5 py-2.5">
                                Montant
                            </th>
                            <th class="px-3.5 py-2.5">
                                Client
                            </th>
                        @endif
                        <th class="px-3.5 py-2.5 text-right w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @forelse($registrations as $reg)
                        @php
                            $typeName = match(is_object($reg->run_registration_type) ? $reg->run_registration_type->value : $reg->run_registration_type) {
                                'company' => 'Entreprise',
                                'school'  => 'Interclasse',
                                'group'   => 'Groupe / Club',
                                default   => ucfirst($reg->run_registration_type)
                            };
                            $conf = $reg->getSchoolConformityDetails();
                        @endphp
                        <tr wire:key="reg-row-{{ $reg->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition">
                            @if($activeTab === 'school')
                                <!-- Interclasses Columns -->
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $reg->school_name ?: 'Centre scolaire' }}
                                    </div>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->school_postal_code }} {{ $reg->school_locality }}
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-mono">
                                        {{ $reg->school_class_level ?: '-' }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $reg->school_class_holder_first_name ?: $reg->contact_first_name }} {{ $reg->school_class_holder_last_name ?: $reg->contact_last_name }}
                                    </div>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->school_class_holder_email ?: $reg->contact_email }}
                                    </div>
                                    @if($reg->school_class_holder_phone ?: $reg->contact_phone)
                                        <div class="text-2xs text-gray-400 font-mono">
                                            {{ $reg->school_class_holder_phone ?: $reg->contact_phone }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-extrabold text-slate-900 dark:text-white font-mono text-sm">
                                        {{ $reg->participants_count }}
                                    </span>
                                    <div class="text-2xs text-gray-500 font-semibold font-mono">
                                        <span class="text-pink-600 dark:text-pink-400">{{ $reg->girls_count }} F</span> / 
                                        <span class="text-sky-600 dark:text-sky-400">{{ $reg->boys_count }} M</span>
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($conf['is_conform'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-2xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            ✅ Conforme ({{ $conf['total'] }} él., {{ $conf['girls'] }} F)
                                        </span>
                                    @else
                                        <div class="space-y-0.5">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-2xs font-bold bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                ⚠️ Incomplet ({{ $conf['total'] }}/{{ $conf['min_students'] }})
                                            </span>
                                            <div class="text-3xs text-rose-600 dark:text-rose-400 font-medium">
                                                @if($conf['missing_students'] > 0)
                                                    Manque {{ $conf['missing_students'] }} élève(s)
                                                @endif
                                                @if($conf['missing_girls'] > 0)
                                                    • Manque {{ $conf['missing_girls'] }} fille(s)
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($reg->client)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-2xs font-medium text-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 rounded border border-emerald-200 dark:border-emerald-800">
                                            {{ str($reg->client->name ?: ($reg->client->invoicing_name ?: 'Client #' . $reg->client->id))->limit(15) }}
                                        </span>
                                    @else
                                        <button 
                                            wire:click="openLinkClientModal({{ $reg->id }})" 
                                            class="text-2xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 hover:underline cursor-pointer"
                                        >
                                            Non lié
                                        </button>
                                    @endif
                                </td>

                            @elseif($activeTab === 'company')
                                <!-- Entreprises Columns -->
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $reg->company_name ?: 'Entreprise' }}
                                    </div>
                                    @if($reg->invoicing_locality)
                                        <div class="text-2xs text-gray-500 font-mono">
                                            {{ $reg->invoicing_postal_code }} {{ $reg->invoicing_locality }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="px-2 py-0.5 text-2xs font-semibold rounded bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        {{ $reg->company_bloc ?: 'Standard' }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $reg->contact_first_name }} {{ $reg->contact_last_name }}
                                    </div>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->contact_email }}
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-extrabold text-slate-900 dark:text-white font-mono text-sm">
                                        {{ $reg->participants_count }}
                                    </span>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->girls_count }} F / {{ $reg->boys_count }} M
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5 font-semibold font-mono text-slate-900 dark:text-white">
                                    {{ number_format($reg->estimated_total, 2, '.', "'") }} CHF
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($reg->client)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-2xs font-medium text-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 rounded border border-emerald-200 dark:border-emerald-800">
                                            {{ str($reg->client->name ?: ($reg->client->invoicing_name ?: 'Client #' . $reg->client->id))->limit(15) }}
                                        </span>
                                    @else
                                        <button 
                                            wire:click="openLinkClientModal({{ $reg->id }})" 
                                            class="text-2xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 hover:underline cursor-pointer"
                                        >
                                            Non lié
                                        </button>
                                    @endif
                                </td>

                            @elseif($activeTab === 'group')
                                <!-- Groupes / Clubs Columns -->
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $reg->company_name ?: 'Groupe / Club' }}
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $reg->contact_first_name }} {{ $reg->contact_last_name }}
                                    </div>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->contact_email }}
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-extrabold text-slate-900 dark:text-white font-mono text-sm">
                                        {{ $reg->participants_count }}
                                    </span>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->girls_count }} F / {{ $reg->boys_count }} M
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5 font-semibold font-mono text-slate-900 dark:text-white">
                                    {{ number_format($reg->estimated_total, 2, '.', "'") }} CHF
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($reg->client)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-2xs font-medium text-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 rounded border border-emerald-200 dark:border-emerald-800">
                                            {{ str($reg->client->name ?: ($reg->client->invoicing_name ?: 'Client #' . $reg->client->id))->limit(15) }}
                                        </span>
                                    @else
                                        <button 
                                            wire:click="openLinkClientModal({{ $reg->id }})" 
                                            class="text-2xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 hover:underline cursor-pointer"
                                        >
                                            Non lié
                                        </button>
                                    @endif
                                </td>

                            @else
                                <!-- Vue Globale (All) Columns -->
                                <td class="px-3.5 py-2.5">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ str($reg->display_name)->limit(28) }}
                                    </div>
                                    @if($reg->school_locality || $reg->invoicing_locality)
                                        <div class="text-2xs text-gray-500 font-mono">
                                            {{ str($reg->school_locality ?: $reg->invoicing_locality)->limit(12) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="px-2 py-0.5 text-2xs font-semibold rounded bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                        {{ $typeName }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-2.5 font-mono font-semibold">
                                    @if($reg->school_class_level)
                                        <span class="px-2 py-0.5 text-2xs rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            {{ $reg->school_class_level }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $reg->contact_first_name }} {{ $reg->contact_last_name }}
                                    </div>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->contact_email }}
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-bold text-slate-900 dark:text-white font-mono text-sm">
                                        {{ $reg->participants_count }}
                                    </span>
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ $reg->girls_count }} F / {{ $reg->boys_count }} M
                                    </div>
                                </td>
                                <td class="px-3.5 py-2.5 font-semibold font-mono text-slate-900 dark:text-white">
                                    {{ number_format($reg->estimated_total, 2, '.', "'") }} CHF
                                </td>
                                <td class="px-3.5 py-2.5">
                                    @if($reg->client)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-2xs font-medium text-emerald-800 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 rounded border border-emerald-200 dark:border-emerald-800">
                                            {{ str($reg->client->name ?: ($reg->client->invoicing_name ?: 'Client #' . $reg->client->id))->limit(12) }}
                                        </span>
                                    @else
                                        <button 
                                            wire:click="openLinkClientModal({{ $reg->id }})" 
                                            class="text-2xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 hover:underline cursor-pointer"
                                        >
                                            Non lié
                                        </button>
                                    @endif
                                </td>
                            @endif

                            <!-- Action Buttons (Icon-Only Compact avec Tooltips) -->
                            <td class="px-3.5 py-2.5 text-right w-28">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    <a 
                                        href="{{ URL::signedRoute('front.run-registration.edit', ['registration' => $reg->id]) }}" 
                                        target="_blank" 
                                        title="Ouvrir le formulaire d'inscription" 
                                        class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white rounded text-xs transition"
                                    >
                                        ✏️
                                    </a>

                                    <button 
                                        wire:click="sendEditLink({{ $reg->id }})" 
                                        wire:confirm="Envoyer le lien d'accès permanent à {{ $reg->contact_email }} ?" 
                                        title="Envoyer l'accès par e-mail au responsable ({{ $reg->contact_email }})" 
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-xs transition cursor-pointer"
                                    >
                                        ✉️
                                    </button>

                                    <button 
                                        wire:click="openLinkClientModal({{ $reg->id }})" 
                                        title="Lier à un client pour facturation" 
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-xs transition cursor-pointer"
                                    >
                                        🔗
                                    </button>

                                    <button 
                                        wire:click="deleteRegistration({{ $reg->id }})" 
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce dossier d'inscription et tous ses participants ?" 
                                        title="Supprimer définitivement ce dossier" 
                                        class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:hover:bg-rose-900 dark:text-rose-300 rounded text-xs transition cursor-pointer"
                                    >
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-xs">
                                Aucun dossier d'inscription trouvé pour cette sélection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($registrations->hasPages())
            <div class="p-3 border-t border-gray-100 dark:border-gray-700">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>

    <!-- Modal d'Association Client -->
    @if($showLinkClientModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full p-5 shadow-xl space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🔗 Associer un client
                    </h3>
                    <button wire:click="$set('showLinkClientModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-xs cursor-pointer">
                        ✖
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <p class="text-gray-600 dark:text-gray-300">
                        Sélectionnez un client existant à associer à ce dossier d'inscription pour la facturation :
                    </p>

                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Client :</label>
                        <select wire:model="selectedClientId" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs dark:text-white">
                            <option value="">Aucun client (Non lié)</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name ?: ($c->invoicing_name ?: 'Client #' . $c->id) }} ({{ $c->email ?? $c->invoicing_email ?? 'Sans mail' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showLinkClientModal', false)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-lg transition cursor-pointer">
                        Annuler
                    </button>
                    <button type="button" wire:click="saveClientLink" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-lg transition cursor-pointer">
                        Enregistrer le lien
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
