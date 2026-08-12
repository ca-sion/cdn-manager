<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-4">
    <!-- Header Banner & Top Controls (Slate Neutral Corporate) -->
    <div class="p-5 bg-slate-900 rounded-xl shadow-md text-white flex flex-col md:flex-row md:items-center justify-between gap-4 border border-slate-800">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 bg-white/10 text-slate-200 rounded text-2xs font-semibold uppercase tracking-wider">
                    Administration
                </span>
                <h1 class="text-xl font-bold tracking-tight text-white">
                    Gestion des inscriptions
                </h1>
            </div>
            <p class="mt-1 text-xs text-slate-300 max-w-2xl leading-normal">
                Supervision des dossiers d'inscription par lot, suivi des décomptes financiers et exportations.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <x-filament::button
                type="button"
                wire:click="exportExcelGlobal"
                color="gray"
                size="sm"
                icon="heroicon-m-arrow-down-tray"
            >
                Export Datasport (.xlsx)
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ route('front.run-registration.create', ['type' => 'company']) }}" 
                color="primary"
                size="sm"
                icon="heroicon-m-plus"
            >
                Nouveau dossier
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

    <!-- Statistics Dashboard Cards (Using transversal model calculations) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total dossiers</div>
            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stats['total_dossiers'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total participants</div>
            <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stats['total_participants'] }} <span class="text-2xs font-normal text-gray-500">coureurs</span></div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entreprises</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $stats['companies_count'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Écoles / Groupes</div>
            <div class="text-xl font-extrabold text-slate-800 dark:text-slate-200 mt-0.5">{{ $stats['schools_count'] + $stats['groups_count'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs col-span-2 sm:col-span-1">
            <div class="text-2xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant estimé cumulé</div>
            <div class="text-lg font-extrabold text-slate-900 dark:text-white mt-0.5 font-mono">{{ number_format($stats['total_estimated'], 2, '.', "'") }} CHF</div>
        </div>
    </div>

    <!-- Search & Filters Bar (Compact) -->
    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <div class="relative flex-1">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Rechercher par société, école, nom du responsable, e-mail, localité..." 
                class="w-full pl-9 pr-3 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs focus:ring-1 focus:ring-slate-500 focus:border-slate-500 dark:text-white transition"
            >
            <span class="absolute left-3 top-2 text-gray-400 text-xs">🔍</span>
        </div>

        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <!-- Filtre par Type -->
            <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                <span class="text-gray-500 font-medium">Type :</span>
                <select wire:model.live="typeFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                    <option value="">Tous les types</option>
                    <option value="company">Entreprises</option>
                    <option value="school">Écoles / Interclasses</option>
                    <option value="group">Groupes / Clubs</option>
                </select>
            </div>

            <!-- Filtre par Client / Facturation -->
            <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-2.5 py-1 rounded-lg text-xs">
                <span class="text-gray-500 font-medium">Client :</span>
                <select wire:model.live="invoiceFilter" class="bg-transparent font-medium text-gray-800 dark:text-gray-200 focus:outline-none cursor-pointer">
                    <option value="">Tous les statuts</option>
                    <option value="linked">Client Lié</option>
                    <option value="unlinked">Non Lié</option>
                </select>
            </div>

            @if(!empty($search) || !empty($typeFilter) || !empty($invoiceFilter))
                <button 
                    type="button"
                    wire:click="$set('search', ''); $set('typeFilter', ''); $set('invoiceFilter', '')" 
                    class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 font-medium text-xs rounded-lg transition"
                >
                    ✖ Réinitialiser
                </button>
            @endif
        </div>
    </div>

    <!-- Table of Registrations (Dossiers) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-900/80 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-200 dark:border-gray-700 select-none text-2xs">
                    <tr>
                        <th wire:click="sortBy('company_name')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                            Société
                        </th>
                        <th wire:click="sortBy('run_registration_type')" class="px-3.5 py-2.5 cursor-pointer hover:bg-gray-100/70 transition">
                            Type
                        </th>
                        <th class="px-3.5 py-2.5">
                            Responsable contact
                        </th>
                        <th class="px-3.5 py-2.5">
                            Part.
                        </th>
                        <th class="px-3.5 py-2.5">
                            Montant (CHF)
                        </th>
                        <th class="px-3.5 py-2.5">
                            Client CRM
                        </th>
                        <th class="px-3.5 py-2.5 text-right">Actions</th>
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
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition">
                            <td class="px-3.5 py-2.5">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ str($reg->display_name)->limit(28) }}
                                </div>
                                @if($reg->school_locality || $reg->invoicing_locality)
                                    <div class="text-2xs text-gray-500 font-mono">
                                        {{ str($reg->school_locality ?: $reg->invoicing_locality)->limit(10) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5">
                                <span class="px-2 py-0.5 text-2xs font-semibold rounded bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                    {{ $typeName }}
                                </span>
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
                                        class="text-2xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 hover:underline"
                                    >
                                        Non lié
                                    </button>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-right">
                                <div class="inline-flex items-center gap-1 justify-end">
                                    <a 
                                        href="{{ URL::signedRoute('front.run-registration.edit', ['registration' => $reg->id]) }}" 
                                        target="_blank" 
                                        title="Ouvrir le formulaire / LaraGrid" 
                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white rounded text-2xs font-medium border border-gray-200 dark:border-gray-600 transition"
                                    >
                                        Ouvrir
                                    </a>

                                    <button 
                                        wire:click="sendEditLink({{ $reg->id }})" 
                                        wire:confirm="Envoyer le lien d'accès permanent à {{ $reg->contact_email }} ?" 
                                        title="Envoyer l'accès par e-mail" 
                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-2xs font-medium border border-slate-200 dark:border-slate-600 transition"
                                    >
                                        Lien
                                    </button>

                                    <button 
                                        wire:click="openLinkClientModal({{ $reg->id }})" 
                                        title="Lier à un client CRM" 
                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 rounded text-2xs font-medium border border-slate-200 dark:border-slate-600 transition"
                                    >
                                        Client
                                    </button>

                                    <button 
                                        wire:click="deleteRegistration({{ $reg->id }})" 
                                        wire:confirm="Êtes-vous sûr de vouloir supprimer ce dossier d'inscription et tous ses participants ?" 
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-xs">
                                Aucun dossier d'inscription trouvé.
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

    <!-- Modal d'Association Client CRM -->
    @if($showLinkClientModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full p-5 shadow-xl space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        🔗 Associer un Client CRM
                    </h3>
                    <button wire:click="$set('showLinkClientModal', false)" class="text-gray-400 hover:text-gray-600 font-bold text-xs">
                        ✖
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <p class="text-gray-600 dark:text-gray-300">
                        Sélectionnez un client existant du CRM à associer à ce dossier d'inscription pour la facturation :
                    </p>

                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Client CRM :</label>
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
                    <button type="button" wire:click="$set('showLinkClientModal', false)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-lg transition">
                        Annuler
                    </button>
                    <button type="button" wire:click="saveClientLink" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-lg transition">
                        Enregistrer le lien
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
