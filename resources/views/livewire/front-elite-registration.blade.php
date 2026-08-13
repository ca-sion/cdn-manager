<div 
    @keydown.window.prevent.cmd.s="$wire.save()" 
    @keydown.window.prevent.ctrl.s="$wire.save()"
>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Header Banner -->
        <div class="p-6 bg-blue-500 rounded-2xl shadow-xl text-white">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-semibold uppercase tracking-wider mb-2">
                        Course Élite
                    </span>
                    <h1 class="text-2xl font-extrabold tracking-tight">Inscription Élite</h1>
                    <p class="mt-2 text-sm text-blue-50 max-w-2xl leading-relaxed">
                        Bienvenue sur la plateforme d'inscription des coureurs Élite de la Course de Noël.
                        Vous pouvez compléter vos informations personnelles. 
                        Toutes vos modifications sont modifiables à tout moment via votre lien sécurisé.
                    </p>
                </div>
                <div class="shrink-0 bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 text-xs space-y-2">
                    <div class="font-bold flex items-center gap-1.5 text-white">
                        Contact
                    </div>
                    <div class="font-mono text-blue-100">{{ setting('elite_manager_email', 'elites@coursedenoel.ch') }}</div>
                    <a href="mailto:{{ setting('elite_manager_email', 'elites@coursedenoel.ch') }}?subject=Question%20Inscription%20Elite" class="inline-block px-3 py-1.5 bg-white text-blue-700 hover:bg-blue-50 font-bold rounded-lg transition shadow-sm">
                        ✉️ Contacter le responsable
                    </a>
                </div>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium flex items-center justify-between shadow-2xs">
                <span>✅ {{ session('message') }}</span>
            </div>
        @endif

        <!-- Top Action Bar (Bouton d'enregistrement haut & Raccourci Ctrl+S / Cmd+S) -->
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                <span class="font-medium">💡 Enregistrement rapide :</span>
                <kbd class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-2xs font-mono font-bold">Ctrl + S</kbd> / <kbd class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-2xs font-mono font-bold">⌘ + S</kbd>
            </div>

            <div class="flex items-center gap-2">
                @if ($this->registration && $this->registration->exists)
                    <button 
                        type="button" 
                        wire:click="sendEmailLink" 
                        wire:confirm="Envoyer le lien de la fiche par e-mail à {{ $this->data['elite_email'] ?? $this->registration->contact_email }} ?" 
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:hover:bg-blue-900 dark:text-blue-300 font-semibold text-xs rounded-xl border border-blue-200 dark:border-blue-800 transition shadow-xs"
                        title="Envoyer le lien d'invitation/fiche"
                    >
                        ✉️ E-mail fiche
                    </button>
                    @if ($isManager)
                        <button 
                            type="button" 
                            wire:click="sendContractEmail" 
                            wire:confirm="Envoyer la confirmation de contrat finalisé avec PDF à {{ $this->data['elite_email'] ?? $this->registration->contact_email }} ?" 
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:hover:bg-emerald-900 dark:text-emerald-300 font-semibold text-xs rounded-xl border border-emerald-200 dark:border-emerald-800 transition shadow-xs"
                            title="Envoyer le contrat finalisé avec PDF"
                        >
                            📄 E-mail contrat
                        </button>
                    @endif
                @endif

                <button 
                    type="button"
                    wire:click="save"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5"
                >
                    <span>💾</span> Enregistrer le dossier
                </button>
            </div>
        </div>

        @if ($element && ($element->has_free_registration_fee || $element->has_bonus_start || $element->bonus_ranking_amount || $element->bonus_arrival_amount || $element->has_accommodation || $element->has_expense_reimbursement))
            <!-- Athlete Contract Conditions Summary Card (Light Filament Card Style) -->
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-2.5 dark:border-gray-700">
                    <h3 class="text-sm font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                        <span>🏆</span> Conditions et engagement
                    </h3>
                    @if ($pdfUrl)
                        <a href="{{ $pdfUrl }}" target="_blank" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 underline flex items-center gap-1">
                            <span>📄</span> Imprimer le contrat (PDF)
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                    @if ($element->has_free_registration_fee)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Frais d'inscription</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">Offerts / Pris en charge</span>
                        </div>
                    @endif

                    @if ($element->has_bonus_start)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Prime de départ</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">
                                Oui {{ $element->bonus_start_amount > 0 ? '('.number_format($element->bonus_start_amount, 2, '.', "'").' CHF)' : '' }}
                            </span>
                        </div>
                    @endif

                    @if ($element->bonus_ranking_amount > 0)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Prime de classement</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($element->bonus_ranking_amount, 2, '.', "'") }} CHF</span>
                        </div>
                    @endif

                    @if ($element->bonus_arrival_amount > 0)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Prime d'arrivée</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ number_format($element->bonus_arrival_amount, 2, '.', "'") }} CHF</span>
                        </div>
                    @endif

                    @if ($element->has_accommodation)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Hébergement</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">Pris en charge</span>
                            <div class="text-2xs text-gray-600 dark:text-gray-300 mt-0.5 font-medium">
                                @if ($element->accommodation_friday) Ven @endif
                                @if ($element->accommodation_saturday) Sam @endif
                                @if ($element->accommodation_precision) ({{ $element->accommodation_precision }}) @endif
                            </div>
                        </div>
                    @endif

                    @if ($element->has_expense_reimbursement)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg border border-gray-200 dark:border-gray-700/60">
                            <span class="text-gray-500 dark:text-gray-400 block text-2xs uppercase tracking-wider font-semibold">Remboursement de frais</span>
                            <span class="font-bold text-emerald-700 dark:text-emerald-400">Pris en charge</span>
                            @if ($element->expense_reimbursement_precision)
                                <div class="text-2xs text-gray-600 dark:text-gray-300 mt-0.5 font-medium">{{ $element->expense_reimbursement_precision }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <form wire:submit.prevent="save" class="space-y-6">
            {{ $this->form }}

            <!-- Bottom Action Bar -->
            <div class="pt-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-4">
                <div class="text-xs text-gray-500 hidden sm:block">
                    Appuyez sur <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border rounded">Ctrl+S</kbd> ou <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border rounded">⌘+S</kbd> pour sauvegarder.
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg hover:shadow-blue-600/30 transition duration-150 flex items-center gap-2">
                    <span>💾</span> Enregistrer le dossier
                </button>
            </div>
        </form>
    </div>
</div>
