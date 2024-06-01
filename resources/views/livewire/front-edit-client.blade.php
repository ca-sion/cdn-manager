<div class="relative w-full max-w-2xl px-6 lg:max-w-7xl mx-auto my-6">

    <span class="text-white text-xs font-medium me-2 px-2.5 py-0.5 rounded" style="background-color:{{ $record->category?->color }};">{{ $record->category?->name }}</span>
    <h1 class="mb-4 mt-2 text-2xl tracking-tight font-bold lg:text-3xl">{{ $record->name }}</h1>
    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Adresse</h2>
            @if ($record->name)
                {{ $record->name }}<br>
            @endif
            @if ($record->address)
                {{ $record->address }}<br>
            @endif
            @if ($record->address_extension)
                {{ $record->address_extension }}<br>
            @endif
            @if ($record->postal_code || $record->locality)
                {{ $record->postal_code }}
                {{ $record->locality }}
                <br>
            @endif
        </div>
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Adresse de facturation</h2>
            @if ($record->name || $record->invoicing_name)
                {{ $record->invoicing_name ?? $record->name }}<br>
            @endif
            @if ($record->address || $record->invoicing_address)
                {{ $record->invoicing_address ?? $record->address }}<br>
            @endif
            @if ($record->address_extension || $record->invoicing_address_extension)
                {{ $record->invoicing_address_extension ?? $record->address_extension }}<br>
            @endif
            @if ($record->postal_code || $record->locality || $record->invoicing_postal_code || $record->invoicing_locality)
                {{ $record->invoicing_postal_code ?? $record->postal_code }}
                {{ $record->invoicing_locality ?? $record->locality }}
                <br>
            @endif
        </div>

        @if ($record->currentProvisionElementsNetAmount() > 0)
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ \App\Models\Edition::find(setting('edition_id'))->name }} - Montant des prestations</h2>
            <div class="space-y-4">
                <div class="space-y-2">
                <dl class="flex items-center justify-between gap-4">
                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Net</dt>
                    <dd class="text-base font-medium text-gray-900 dark:text-white">{{ \App\Classes\Price::of($record->currentProvisionElementsNetAmount())->amount('c') }}</dd>
                </dl>

                <dl class="flex items-center justify-between gap-4">
                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">TVA</dt>
                    <dd class="text-base font-medium text-gray-900 dark:text-white">{{ \App\Classes\Price::of($record->currentProvisionElementsTaxAmount())->amount('c') }}</dd>
                </dl>
                </div>

                <dl class="flex items-center justify-between gap-4 border-t border-gray-200 pt-2 dark:border-gray-700">
                <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
                <dd class="text-base font-bold text-gray-900 dark:text-white">{{ \App\Classes\Price::of($record->currentProvisionElementsAmount())->amount('c') }}</dd>
                </dl>
            </div>
        </div>
        @endif
        <div>
            <div class="mb-4">
                <a href="{{ $record->pdfLink }}" target="_blank" class="w-full inline-flex items-center justify-center p-5 text-base font-medium text-gray-900 rounded-lg bg-gray-100 hover:text-gray-800 hover:bg-gray-200 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white">
                    <span class="w-full">Consulter la fiche PDF</span>
                    <svg class="w-4 h-4 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
            </div>

            <div class="mb-4">
                <a href="mailto:info@coursedenoel.ch" class="w-full inline-flex items-center justify-center p-5 text-base font-medium text-gray-900 rounded-lg bg-primary-50 hover:text-gray-800 hover:bg-primary-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white">
                    <span class="w-full">Vous souhaitez modifier une donnée ou un élément ou vous avez une question ? Contactez-nous.</span>
                    <svg class="w-4 h-4 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <form wire:submit="save">

        <x-filament::button
            type="submit"
            size="xl"
            class="mb-4"
            color="primary"
        >
        Enregister les modifications
        </x-filament::button>

        {{ $this->form }}

        <x-filament::button
            type="submit"
            size="xl"
            class="mt-4"
            color="primary"
        >
        Enregister les modifications
        </x-filament::button>

    </form>

    <x-filament-actions::modals />
</div>
