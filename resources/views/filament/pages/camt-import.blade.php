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
                                    ({{ $transaction->invoice?->status }})
                                @endif
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
