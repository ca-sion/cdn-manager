<div class="relative w-full max-w-2xl px-6 lg:max-w-7xl mx-auto my-6">
    <form wire:submit="update">
        {{ $this->form }}

        <x-filament::button
            class="mt-6"
            type="submit"
            size="lg"
        >
            Enregistrer et envoyer
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
