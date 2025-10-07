<div class="relative mx-auto my-6 w-full max-w-2xl px-6">
    <div style="width: 80px;" class="mb-6"><x-pdf.logo-cdn /></div>
    <h1 class="mb-4 text-md">Bonjour,</h1>
    <h1 class="mb-4 text-xl">Avez-vous déjà été annonceur de la Course de Noël ?</h1>

    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" size="sm" class="mt-6">
            Suivant
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
