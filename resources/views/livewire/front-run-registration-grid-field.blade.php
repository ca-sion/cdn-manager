<div class="space-y-4" x-data="{ activeTab: 'paste' }">
    <!-- En-tête & Boutons d'Action (Sober, Clair et Élégant) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gray-50 dark:bg-gray-800/60 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs">
        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
            <span class="text-base">📋</span>
            <span>Naviguez avec <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded font-mono text-2xs shadow-2xs">Tab</kbd> ou <kbd class="px-1.5 py-0.5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded font-mono text-2xs shadow-2xs">Entrée</kbd>. Dates au format <code class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">jj.mm.aaaa</code>.</span>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            @if(! $this->isGridLocked())
                <x-filament::button
                    type="button"
                    wire:click="openImportModal"
                    color="gray"
                    size="sm"
                    icon="heroicon-m-document-duplicate"
                >
                    Coller / Importer Excel
                </x-filament::button>

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
                Vérifier les données
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

    <!-- Tableau de Saisie Réactif (Style Tableur Excel) -->
    <div class="w-full overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-900">
        <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-700"
               x-data="{
                   focusGrid(r, c) {
                       let el = $el.querySelector(`[data-row='${r}'][data-col='${c}']`);
                       if (el) {
                           el.focus();
                           if (typeof el.select === 'function') el.select();
                       } else if (r >= {{ count($this->elements) }}) {
                           $wire.addRow().then(() => {
                               setTimeout(() => this.focusGrid(r, c), 50);
                           });
                       }
                   }
               }">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold uppercase tracking-wider text-2xs">
                <tr>
                    <th class="px-3 py-2.5 w-10 text-center">#</th>
                    <th class="px-3 py-2.5 min-w-[140px]">Prénom</th>
                    <th class="px-3 py-2.5 min-w-[140px]">Nom</th>
                    <th class="px-3 py-2.5 min-w-[170px]">Date de naissance</th>
                    <th class="px-3 py-2.5 w-24">Genre</th>
                    <th class="px-3 py-2.5 min-w-[150px]">Nationalité</th>
                    @if(in_array($this->type, ['group', 'company']))
                        <th class="px-3 py-2.5 min-w-[180px]">Email</th>
                    @endif
                    @if($this->type === 'group')
                        <th class="px-3 py-2.5 min-w-[280px]">Course</th>
                    @endif
                    @if(in_array($this->type, ['group', 'company']))
                        <th class="px-3 py-2.5 w-16 text-center">Vidéo</th>
                    @endif
                    @if(! $this->isGridLocked())
                        <th class="px-3 py-2.5 w-12 text-center"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($this->elements as $index => $row)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-3 py-2 text-center text-gray-400 font-mono text-xs font-semibold">{{ $loop->iteration }}</td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="elements.{{ $index }}.first_name"
                                   placeholder="Prénom"
                                   data-row="{{ $index }}" data-col="0"
                                   @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 0)"
                                   @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 0)"
                                   @keydown.enter.prevent="focusGrid({{ $index + 1 }}, 0)"
                                   @disabled($this->isGridLocked())
                                   class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-2xs" />
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.blur="elements.{{ $index }}.last_name"
                                   placeholder="Nom"
                                   data-row="{{ $index }}" data-col="1"
                                   @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 1)"
                                   @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 1)"
                                   @keydown.enter.prevent="focusGrid({{ $index + 1 }}, 1)"
                                   @disabled($this->isGridLocked())
                                   class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-2xs" />
                        </td>
                        <td class="px-2 py-1.5">
                            <input type="text"
                                   wire:model.live.debounce.300ms="elements.{{ $index }}.birthdate"
                                   x-mask="99.99.9999"
                                   placeholder="jj.mm.aaaa"
                                   data-row="{{ $index }}" data-col="2"
                                   @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 2)"
                                   @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 2)"
                                   @keydown.enter.prevent="focusGrid({{ $index + 1 }}, 2)"
                                   @disabled($this->isGridLocked())
                                   class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 font-mono shadow-2xs" />
                        </td>
                        <td class="px-2 py-1.5">
                            <select wire:model.live="elements.{{ $index }}.gender"
                                    data-row="{{ $index }}" data-col="3"
                                    @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 3)"
                                    @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 3)"
                                    @disabled($this->isGridLocked())
                                    class="w-full px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-2xs">
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </td>
                        <td class="px-2 py-1.5">
                            <select wire:model.live="elements.{{ $index }}.nationality"
                                    data-row="{{ $index }}" data-col="4"
                                    @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 4)"
                                    @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 4)"
                                    @disabled($this->isGridLocked())
                                    class="w-full px-2 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-2xs">
                                @foreach(\App\Helpers\CountryHelper::getOptions() as $code => $country)
                                    <option value="{{ $code }}">{{ $country }}</option>
                                @endforeach
                            </select>
                        </td>
                        @if(in_array($this->type, ['group', 'company']))
                            <td class="px-2 py-1.5">
                                <input type="email"
                                       wire:model.blur="elements.{{ $index }}.email"
                                       placeholder="email@example.com"
                                       data-row="{{ $index }}" data-col="5"
                                       @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 5)"
                                       @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 5)"
                                       @keydown.enter.prevent="focusGrid({{ $index + 1 }}, 5)"
                                       @disabled($this->isGridLocked())
                                       class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-2xs" />
                            </td>
                        @endif
                        @if($this->type === 'group')
                            <td class="px-2 py-1.5">
                                <select wire:model.live="elements.{{ $index }}.run_id"
                                        data-row="{{ $index }}" data-col="6"
                                        @keydown.arrow-down.prevent="focusGrid({{ $index + 1 }}, 6)"
                                        @keydown.arrow-up.prevent="focusGrid({{ $index - 1 }}, 6)"
                                        @disabled($this->isGridLocked())
                                        class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 font-medium shadow-2xs">
                                    <option value="">-- Choisir une course --</option>
                                    @foreach($this->getRunsForBirthdate($row['birthdate'] ?? '') as $rId => $rLabel)
                                        <option value="{{ $rId }}">{{ $rLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                        @endif
                        @if(in_array($this->type, ['group', 'company']))
                            <td class="px-2 py-1.5 text-center">
                                <input type="checkbox"
                                       wire:model.live="elements.{{ $index }}.with_video"
                                       @disabled($this->isGridLocked())
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4" />
                            </td>
                        @endif
                        @if(! $this->isGridLocked())
                            <td class="px-2 py-1.5 text-center">
                                <button type="button"
                                        wire:click="removeRow({{ $index }})"
                                        class="text-gray-400 hover:text-red-500 transition-colors p-1"
                                        title="Supprimer la ligne">
                                    🗑️
                                </button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- MODAL D'IMPORTATION / COPIER-COLLER EXCEL -->
    @if($this->showImportModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Header Modal -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <span>📊</span>
                        <span>Importer ou Coller des Participants</span>
                    </h3>
                    <button type="button" wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg font-bold">×</button>
                </div>

                <!-- Body Modal -->
                <div class="p-6 space-y-5">
                    <!-- Format Exigé (Exemple Visuel) -->
                    <div class="p-3.5 bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-xl space-y-2 text-xs">
                        <div class="font-bold text-indigo-950 dark:text-indigo-200 flex items-center gap-1.5">
                            <span>💡</span>
                            <span>Format des colonnes attendu dans Excel :</span>
                        </div>
                        <div class="font-mono bg-white dark:bg-gray-900 p-2.5 rounded-lg border border-indigo-100 dark:border-indigo-900 text-2xs overflow-x-auto text-indigo-900 dark:text-indigo-300">
                            Prénom &nbsp;|&nbsp; Nom &nbsp;|&nbsp; Date de naissance (jj.mm.aaaa) &nbsp;|&nbsp; Genre (M/F) &nbsp;|&nbsp; Email &nbsp;|&nbsp; Nationalité (ex: SUI)
                        </div>
                        <div class="text-gray-600 dark:text-gray-400 text-2xs">
                            Exemple : <code class="font-mono font-bold text-gray-900 dark:text-gray-200">Lucas &nbsp; Rey &nbsp; 02.02.2018 &nbsp; M &nbsp; lucas@example.com &nbsp; SUI</code>
                        </div>
                    </div>

                    <!-- Onglets Choisis (Copier-Coller vs Fichier Excel) -->
                    <div class="flex border-b border-gray-200 dark:border-gray-700 text-xs font-semibold">
                        <button type="button"
                                @click="activeTab = 'paste'"
                                :class="activeTab === 'paste' ? 'border-primary-600 text-primary-600 border-b-2' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2.5 transition-colors">
                            📋 Copier-Coller du Texte
                        </button>
                        <button type="button"
                                @click="activeTab = 'file'"
                                :class="activeTab === 'file' ? 'border-primary-600 text-primary-600 border-b-2' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2.5 transition-colors">
                            📁 Importer un fichier (.xlsx / .csv)
                        </button>
                    </div>

                    <!-- Mode 1 : Copier-Coller Texte -->
                    <div x-show="activeTab === 'paste'" class="space-y-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Collez ici vos lignes copiées directement depuis Excel :
                        </label>
                        <textarea wire:model="pasteTextData"
                                  rows="6"
                                  placeholder="Copiez vos colonnes depuis Excel et collez-les ici...&#10;Lucas&#9;Rey&#9;02.02.2018&#9;M&#9;lucas@example.com&#9;SUI&#10;Emma&#9;Dubois&#9;15.08.2008&#9;F&#9;emma@example.com&#9;SUI"
                                  class="w-full font-mono text-xs rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-3 focus:ring-primary-500 focus:border-primary-500"></textarea>

                        <div class="flex justify-end gap-2 pt-2">
                            <x-filament::button type="button" wire:click="closeImportModal" color="gray" size="sm">
                                Annuler
                            </x-filament::button>
                            <x-filament::button type="button" wire:click="processPasteText" color="primary" size="sm" icon="heroicon-m-check">
                                Importer les lignes
                            </x-filament::button>
                        </div>
                    </div>

                    <!-- Mode 2 : Fichier Excel / CSV -->
                    <div x-show="activeTab === 'file'" class="space-y-3">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                            Sélectionnez un fichier Excel ou CSV (.xlsx, .csv) :
                        </label>
                        <input type="file"
                               wire:model="importFile"
                               accept=".xlsx,.csv"
                               class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-800 dark:file:text-gray-200" />

                        <div class="flex justify-end gap-2 pt-2">
                            <x-filament::button type="button" wire:click="closeImportModal" color="gray" size="sm">
                                Annuler
                            </x-filament::button>
                            <x-filament::button type="button" wire:click="processExcelImport" color="primary" size="sm" icon="heroicon-m-arrow-up-tray">
                                Téléverser et Traiter
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- BLOC DE VÉRIFICATION D'INTÉGRITÉ COMPACT -->
    @if($this->integrityChecked)
        @if(count($this->integrityErrors) > 0)
            <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl space-y-2.5 text-xs shadow-sm">
                <div class="flex items-center justify-between font-bold text-amber-900 dark:text-amber-200">
                    <span class="flex items-center gap-1.5 text-sm">
                        <span>🔍</span>
                        <span>Rapport de vérification : {{ count($this->integrityErrors) }} participant(s) non conforme(s)</span>
                    </span>
                    <span class="text-xs font-normal text-amber-700 dark:text-amber-300">Veuillez corriger les anomalies avant d'enregistrer</span>
                </div>

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
                    <span><strong>Vérification réussie :</strong> Aucune anomalie sur les données des participants.</span>
                </div>
                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ count(array_filter($this->elements, fn($r) => !empty($r['first_name']) || !empty($r['last_name']))) }} participant(s) conforme(s)</span>
            </div>
        @endif
    @endif

    <!-- Footer Récapitulatif : Nombre de participants (sur les 3 types) & Total estimé CHF -->
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
