<div class="relative w-full max-w-2xl px-6 lg:max-w-7xl mx-auto my-6">
    <form wire:submit="create">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</div>
