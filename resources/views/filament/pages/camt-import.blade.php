<x-filament-panels::page>

    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <x-filament::button class="mt-2" type="submit">
            Importer le fichier
        </x-filament::button>
    </form>

    <x-filament-actions::modals />

    @if ($transactions)

        <div class="relative overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 rtl:text-right">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3" scope="col">
                            Débiteur
                        </th>
                        <th class="px-6 py-3" scope="col">
                            Montant
                        </th>
                        <th class="px-6 py-3" scope="col">
                            Date
                        </th>
                        <th class="px-6 py-3" scope="col">
                            Facture liée
                        </th>
                        <th class="px-6 py-3" scope="col">
                            Statut
                        </th>
                        <th class="px-6 py-3" scope="col">
                            Référence QR
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <th class="px-6 py-4" scope="row">
                                {{ $transaction->debtor }}
                            </th>
                            <td class="px-6 py-4 text-end">
                                {{ $transaction->amount }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                {{ $transaction->date->locale('fr_CH')->isoFormat('L') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($transaction->invoice)
                                    <a href="{{ route('filament.admin.resources.invoices.edit', ['record' => $transaction->invoice?->id]) }}">{{ $transaction->invoice?->number }}</a>
                                    <a class="text-xs" href="{{ $transaction->invoice?->link }}">📄</a>
                                    <a class="text-xs" href="{{ route('filament.admin.resources.clients.edit', ['record' => $transaction->invoice?->client?->id, 'activeRelationManager' => 3]) }}">👤</a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="fi-badge fi-color-custom me-1 flex min-w-[theme(spacing.6)] items-center justify-center gap-x-1 rounded-md bg-custom-50 px-2 py-1 text-xs font-medium text-custom-600 ring-1 ring-inset ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30" style="--c-50:var(--{{ $transaction->invoice?->status?->getColor() }}-50);--c-400:var(--{{ $transaction->invoice?->status?->getColor() }}-400);--c-600:var(--{{ $transaction->invoice?->status?->getColor() }}-600);">
                                    {{ $transaction->invoice?->status?->getLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $transaction->qr_reference }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-filament-panels::page>
