<div class="space-y-4" x-data @change.debounce.300ms="$wire.$refresh()" @lgrid-change.window="$wire.$refresh()">
    <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500">
            Saisie interactive style tableur Excel. Utilisez les touches Tabulation et Entrée pour vous déplacer.
        </span>
        @if ($this->isGridLocked())
            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-800">
                🔒 Grille verrouillée
            </span>
        @else
            <x-filament::button
                type="button"
                wire:click="addRow"
                color="primary"
                size="sm"
                icon="heroicon-m-plus"
            >
                Ajouter un participant
            </x-filament::button>
        @endif
    </div>

    <div class="w-full overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
        <x-laragrid :grid="$this->gridDefinition('elements')" :rows="$this->elements" />
    </div>

    @if (in_array($this->type, ['company', 'group']))
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-indigo-50 border border-indigo-200 p-4 rounded-xl text-indigo-950 font-medium text-sm shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Participants renseignés : <strong>{{ count(array_filter($this->elements, fn($r) => !empty($r['first_name']) || !empty($r['last_name']))) }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wider font-semibold text-indigo-700">Total estimé :</span>
                <span class="text-base font-bold font-mono text-indigo-900 bg-white px-3 py-1 rounded-md border border-indigo-200 shadow-xs">
                    {{ number_format($this->estimated_total, 2) }} CHF
                </span>
            </div>
        </div>
    @endif
</div>
