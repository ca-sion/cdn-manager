<div class="relative w-full max-w-2xl px-6 lg:max-w-7xl mx-auto my-6">
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            size="xl"
            class="mt-4"
            color="success"
        >
        Enregister les modifications
        </x-filament::button>

    </form>

    <x-filament-actions::modals />
</div>
