<x-layout>

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Client
                    </th>
                    <th scope="col" class="px-6 py-3">

                    </th>
                    <th scope="col" class="px-6 py-3">
                        Contacts
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Documents
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Factures
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Prestations
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <div class="font-bold">{{ $client->name }}</div>
                        <div class="text-xs text-gray-700">{{ $client->long_name }}</div>
                        <div class="text-xs ms-2 text-gray-700">
                            @if ($client->address)
                            {{ $client->address }}<br>
                            @endif
                            @if ($client->address_extension)
                            {{ $client->address_extension }}<br>
                            @endif
                            @if ($client->postal_code || $client->locality)
                            {{ $client->postal_code }}
                            {{ $client->locality }}
                            @endif
                        </div>
                    </th>
                    <td class="px-6 py-4">
                        <span class="text-white text-xs font-medium me-2 px-2.5 py-0.5 rounded" style="background-color: {{ $client->category?->color }};">{{ $client->category?->name }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @foreach ($client->contacts as $contact)
                        <div class="text-sm">
                            {{ $contact->name }}
                            @if ($contact->pivot->type)
                            <span class="text-xs">({{ $contact->pivot->type }})</span>
                            @endif
                            @if ($contact->email)
                            <a href="mailto:{{ $contact->email }}">@</a>
                            @endif
                        </div>
                        @endforeach
                    </td>
                    <td class="px-6 py-4">
                        @foreach ($client->documents as $document)
                        <div class="text-sm">
                            <div>
                                {{ $document->type }}
                                {{ \Carbon\Carbon::parse($document->date)->format('Y') }}
                                @foreach ($document->getMedia('*') as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank">d</a>
                                @endforeach
                                <span></span>
                            </div>
                        </div>
                        @endforeach
                    </td>
                    <td class="px-6 py-4">
                        @foreach ($client->invoices as $invoice)
                        <div class="text-sm">
                            <div>
                                <a href="{{ $invoice->link }}" target="_blank">
                                    {{ $invoice->status }}
                                    {{ $invoice->number }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </td>
                    <td class="px-6 py-4">
                        <table>
                            @foreach ($client->provisionElements as $provision)
                            <tr>
                                <td>{{ $provision->status }}</td>
                                <td>{{ $provision->provision?->name }}</td>
                                <td>{{ $provision->precision }}</td>
                                <td>{{ \App\services\PricingService::format(\App\services\PricingService::applyQuantity(\App\services\PricingService::calculateCostPrice($provision->cost, $provision->tax_rate, $provision->include_vat), $provision->quantity)) }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-layout>
