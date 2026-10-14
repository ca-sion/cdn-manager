<x-filament-panels::page>

    <div class="space-y-6">
        @if (! $transactions)
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <form wire:submit.prevent="submit" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex items-center gap-3 pt-2">
                        <x-filament::button type="submit" icon="heroicon-o-arrow-up-tray" size="lg">
                            Analyser les fichiers & Traiter
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @endif

        @if ($summary && $transactions)
            {{-- Cartes de synthèse / KPIs --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total écritures</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_transactions'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">{{ $summary['files_count'] ?? 1 }} fichier(s)</div>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Factures réconciliées</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summary['reconciled_invoices'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">Passées en payées</div>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Autres transactions</div>
                    <div class="mt-1 text-2xl font-bold text-sky-600 dark:text-sky-400">{{ ($summary['other_transactions'] ?? 0) + ($summary['unmatched_payments'] ?? 0) }}</div>
                    <div class="text-xs text-gray-400">Salaires, frais, etc.</div>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Écarts montant</div>
                    <div class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $summary['mismatched_amounts'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">À vérifier</div>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Doublons ignorés</div>
                    <div class="mt-1 text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $summary['duplicate_entries'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">A89 & croisements</div>
                </div>

                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Volume net (CHF)</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                        +{{ number_format((float) ($summary['total_credit'] ?? 0), 2, '.', "'") }}
                    </div>
                    <div class="text-xs font-medium text-rose-500">
                        -{{ number_format((float) ($summary['total_debit'] ?? 0), 2, '.', "'") }}
                    </div>
                </div>
            </div>

            {{-- Actions d'exportation Banana --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:ring-emerald-800"
                 x-data="{ copied: false, tsv: @js($tsvContent) }">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-white">
                        <x-filament::icon icon="heroicon-o-document-arrow-down" class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-emerald-950 dark:text-emerald-100">Écritures prêtes pour Banana Comptabilité Plus</h3>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">
                            Format tabulé conforme (en-têtes XML). Dans Banana : <em>Actions > Importer en comptabilité > Écritures</em>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-filament::button wire:click="downloadBananaFile" color="success" icon="heroicon-o-arrow-down-tray">
                        Télécharger le fichier Banana (.txt)
                    </x-filament::button>

                    <button type="button"
                            x-on:click="navigator.clipboard.writeText(tsv); copied = true; setTimeout(() => copied = false, 3000)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-2 text-xs font-medium text-emerald-800 shadow-sm transition hover:bg-emerald-50 dark:border-emerald-700 dark:bg-gray-800 dark:text-emerald-200 dark:hover:bg-gray-700">
                        <x-filament::icon icon="heroicon-o-clipboard-document" class="h-4 w-4" />
                        <span x-text="copied ? 'Copié dans le presse-papier !' : 'Copier pour Banana'"></span>
                    </button>

                    <x-filament::button wire:click="resetImport" color="gray" icon="heroicon-o-arrow-path">
                        Nouvel import
                    </x-filament::button>
                </div>
            </div>

            {{-- Tableau détaillé des écritures --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3" scope="col">Date</th>
                                <th class="px-4 py-3" scope="col">Pièce / Facture</th>
                                <th class="px-4 py-3" scope="col">Débit</th>
                                <th class="px-4 py-3" scope="col">Crédit</th>
                                <th class="px-4 py-3 text-right" scope="col">Montant CHF</th>
                                <th class="px-4 py-3" scope="col">TVA</th>
                                <th class="px-4 py-3" scope="col">Libellé (Description)</th>
                                <th class="px-4 py-3" scope="col">Rapprochement</th>
                                <th class="px-3 py-3 text-center" scope="col">Règle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($transactions as $index => $tx)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                        {{ $tx['formatted_date'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        @if (! empty($tx['doc_invoice']))
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                                F{{ $tx['doc_invoice'] }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $tx['account_debit'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-blue-600 dark:text-blue-400">
                                        {{ $tx['account_credit'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs font-bold {{ $tx['sens'] === 'CRDT' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $tx['formatted_amount'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500">
                                        {{ $tx['vat_code'] ?: '—' }}
                                    </td>
                                    <td class="max-w-md px-4 py-3 text-xs text-gray-800 dark:text-gray-200" title="{{ $tx['description'] }}">
                                        <div class="line-clamp-2">
                                            {{ $tx['description'] }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs">
                                        @if ($tx['invoice'])
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if (! empty($tx['is_partial']))
                                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400"
                                                          title="Facture totale : {{ number_format((float) $tx['invoice']->total, 2, '.', '\'') }} CHF">
                                                        Acompte (Reste dû : {{ number_format((float) $tx['remaining_balance'], 2, '.', '\'') }} CHF)
                                                    </span>
                                                @elseif (! empty($tx['is_overpaid']))
                                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-400"
                                                          title="Facture totale : {{ number_format((float) $tx['invoice']->total, 2, '.', '\'') }} CHF">
                                                        Trop-perçu (+{{ number_format((float) $tx['overpaid_amount'], 2, '.', '\'') }} CHF)
                                                    </span>
                                                @else
                                                    <span class="fi-badge fi-color-custom flex items-center gap-x-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400">
                                                        Payée
                                                    </span>
                                                @endif

                                                <a href="{{ route('filament.admin.resources.invoices.edit', ['record' => $tx['invoice']->id]) }}"
                                                   target="_blank"
                                                   class="text-primary-600 hover:underline text-xs"
                                                   title="Voir la facture #{{ $tx['invoice']->number }}">
                                                    📄
                                                </a>
                                                @if ($tx['invoice']->client)
                                                    <a href="{{ route('filament.admin.resources.clients.edit', ['record' => $tx['invoice']->client->id, 'activeRelationManager' => 3]) }}"
                                                       target="_blank"
                                                       class="text-gray-500 hover:underline text-xs"
                                                       title="Voir le client {{ $tx['invoice']->client->name }}">
                                                        👤
                                                    </a>
                                                @endif
                                            </div>
                                        @elseif (! empty($tx['qr_reference']))
                                            <span class="rounded bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400"
                                                  title="Référence QR inconnue dans CDN : {{ $tx['qr_reference'] }}">
                                                Non réconciliée
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">Hors QR</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-center">
                                        <button type="button"
                                                wire:click="mountAction('createRule', {
                                                    keyword: @js($tx['party_name'] ?: preg_replace('/^(Facture|Ordre|Paiement)\s+/i', '', $tx['description'])),
                                                    account: @js($tx['sens'] === 'DBIT' ? $tx['account_debit'] : $tx['account_credit']),
                                                    vat_code: @js($tx['vat_code'] ?: '')
                                                })"
                                                class="inline-flex items-center justify-center rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400"
                                                title="Mémoriser une règle pour ce partenaire dans les Paramètres">
                                            <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />

</x-filament-panels::page>
