<div class="space-y-4">
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
</div>
