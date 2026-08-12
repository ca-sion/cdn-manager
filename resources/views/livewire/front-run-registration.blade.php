<div 
    @keydown.window.prevent.cmd.s="$wire.save()" 
    @keydown.window.prevent.ctrl.s="$wire.save()"
    class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6"
>
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- En-tête bleu élégant avec explication du processus & contact -->
    <div class="p-6 bg-blue-500 rounded-2xl shadow-xl text-white">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-semibold uppercase tracking-wider mb-2">
                    {{ match($type) {
                        'company' => 'Entreprise',
                        'school'  => 'Interclasse',
                        'group'   => 'Groupe / Club',
                        default   => ucfirst($type)
                    } }}
                </span>
                <h1 class="text-2xl font-extrabold tracking-tight">Formulaire d'inscription — Course de Noël</h1>
                <p class="mt-2 text-sm text-blue-50 max-w-3xl leading-relaxed">
                    Bienvenue sur la plateforme d'inscription. Vous pouvez enregistrer et modifier la liste de vos participants à tout moment jusqu'à la date limite de clôture des inscriptions. 
                    Un e-mail de confirmation contenant votre <strong>lien d'accès permanent</strong> vous sera automatiquement envoyé dès l'enregistrement.
                </p>
            </div>
            <div class="shrink-0 bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 text-xs space-y-2">
                <div class="font-bold flex items-center gap-1.5 text-white">
                    Une question ?
                </div>
                <div class="font-mono text-blue-100">{{ setting('general_registration_email', 'inscriptions@coursedenoel.ch') }}</div>
                <a href="mailto:{{ setting('general_registration_email', 'inscriptions@coursedenoel.ch') }}?subject=Question%20Inscription" class="inline-block px-3 py-1.5 bg-white text-blue-700 hover:bg-blue-50 font-bold rounded-lg transition shadow-sm">
                    ✉️ Contacter-nous
                </a>
            </div>
        </div>
    </div>

    <!-- Top Action Bar (Bouton d'enregistrement haut & Raccourci Ctrl+S / Cmd+S) -->
    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
            <span class="font-medium">💡 Enregistrement rapide :</span>
            <kbd class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-2xs font-mono font-bold">Ctrl + S</kbd> / <kbd class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded text-2xs font-mono font-bold">⌘ + S</kbd>
        </div>

        <x-filament::button
            type="button"
            wire:click="save"
            size="md"
            color="primary"
            icon="heroicon-m-check"
        >
            Enregistrer l'inscription
        </x-filament::button>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <!-- Bottom Action Bar -->
        <div class="flex items-center justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 hidden sm:block">
                Appuyez sur <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border rounded">Ctrl+S</kbd> ou <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 border rounded">⌘+S</kbd> pour sauvegarder.
            </div>

            <x-filament::button
                type="submit"
                size="lg"
                color="primary"
                icon="heroicon-m-check"
            >
                Enregistrer l'inscription
            </x-filament::button>
        </div>
    </form>
</div>