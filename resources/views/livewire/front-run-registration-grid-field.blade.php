<div class="space-y-4" @change.debounce.300ms="$wire.$refresh()" @lgrid-change.window="$wire.$refresh()">
    <!-- En-tête & Raccourcis Clavier Simples (Tableur Neutre) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <span class="text-xs text-gray-500">
            Saisie style tableur Excel. Naviguez avec <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs">Tab</kbd> / <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs">Entrée</kbd>. Effacez avec <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs">Suppr</kbd>. Supprimez une ligne avec <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded text-xs">Shift+Suppr</kbd> ou l'icône 🗑️.
        </span>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @if(! $this->isGridLocked())
                <x-filament::button
                    type="button"
                    wire:click="cleanEmptyRows"
                    color="gray"
                    size="sm"
                    icon="heroicon-m-sparkles"
                >
                    Nettoyer les lignes vides
                </x-filament::button>
            @endif

            <x-filament::button
                type="button"
                wire:click="verifyIntegrity"
                color="warning"
                size="sm"
                icon="heroicon-m-magnifying-glass-circle"
            >
                Vérifier l'intégrité des données
            </x-filament::button>

            @if (! $this->isGridLocked())
                <x-filament::button
                    type="button"
                    wire:click="addRow"
                    color="primary"
                    size="sm"
                    icon="heroicon-m-plus"
                >
                    Ajouter une ligne
                </x-filament::button>
            @endif
        </div>
    </div>

    <!-- Tableau Excel LaraGrid (Style Neutre) -->
    <div class="w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <x-laragrid :grid="$this->gridDefinition('elements')" :rows="$this->elements" />
    </div>

    <!-- BLOC DE VÉRIFICATION D'INTÉGRITÉ COMPACT (PLACÉ SOUS LE LARAGRID) -->
    @if($this->integrityChecked)
        @if(count($this->integrityErrors) > 0)
            <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl space-y-2.5 text-xs shadow-sm">
                <div class="flex items-center justify-between font-bold text-amber-900 dark:text-amber-200">
                    <span class="flex items-center gap-1.5 text-sm">
                        <span>🔍</span>
                        <span>Rapport d'intégrité : {{ count($this->integrityErrors) }} participant(s) non conforme(s)</span>
                    </span>
                    <span class="text-xs font-normal text-amber-700 dark:text-amber-300">Veuillez corriger les anomalies avant d'enregistrer</span>
                </div>

                <!-- Tableau compact et lisible des anomalies -->
                <div class="overflow-x-auto border border-amber-200 dark:border-amber-900/60 rounded-lg bg-white dark:bg-gray-900">
                    <table class="w-full text-left text-xs divide-y divide-amber-100 dark:divide-amber-900/40">
                        <thead class="bg-amber-100/70 dark:bg-amber-950/80 font-bold text-amber-900 dark:text-amber-200">
                            <tr>
                                <th class="px-3 py-1.5 w-16">Ligne</th>
                                <th class="px-3 py-1.5 w-44">Participant</th>
                                <th class="px-3 py-1.5">Anomalie(s) détectée(s)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100 dark:divide-amber-900/30 text-gray-800 dark:text-gray-200">
                            @foreach($this->integrityErrors as $err)
                                <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-950/20">
                                    <td class="px-3 py-1.5 font-mono font-bold text-amber-600 dark:text-amber-400">#{{ $err['row'] }}</td>
                                    <td class="px-3 py-1.5 font-medium">{{ $err['label'] }}</td>
                                    <td class="px-3 py-1.5">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($err['errors'] as $msg)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 font-semibold text-2xs border border-amber-200 dark:border-amber-800">
                                                    {{ $msg }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span><strong>Intégrité vérifiée avec succès :</strong> Aucune anomalie sur les données des participants.</span>
                </div>
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ count(array_filter($this->elements, fn($r) => !empty($r['first_name']) || !empty($r['last_name']))) }} participant(s) conforme(s)</span>
            </div>
        @endif
    @endif

    <!-- Footer Récapitulatif : Nombre de participants (sur les 3 types) & Total estimé CHF (uniquement company/group) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 rounded-xl text-gray-800 dark:text-gray-200 font-medium text-sm shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-lg">👥</span>
            <span>Nombre de participants : <strong class="text-indigo-600 dark:text-indigo-400 font-mono text-base">{{ count(array_filter($this->elements, fn($r) => !empty($r['first_name']) || !empty($r['last_name']))) }}</strong></span>
        </div>

        @if(in_array($this->type, ['company', 'group']))
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wider font-semibold text-gray-500">Total estimé :</span>
                <span class="text-base font-bold font-mono text-emerald-700 dark:text-emerald-400 bg-white dark:bg-gray-900 px-3.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-800 shadow-xs">
                    {{ number_format($this->estimated_total, 2, '.', "'") }} CHF
                </span>
            </div>
        @endif
    </div>
</div>
