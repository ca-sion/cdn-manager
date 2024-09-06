<x-layouts.app>
<form action="" method="GET" class="flex gap-4 m-4">
    <div>
        <label for="category" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catégorie</label>
        <select name="category" id="category" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="">Toutes</option>
            @foreach ($clientCategories as $category)
            <option value="{{ $category->id }}" @selected($categoryId == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="provisionCategory" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catégorie de prestation</label>
        <select name="provision_category" id="provisionCategory" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="">Toutes</option>
            @foreach ($provisionCategories as $provisionCategory)
            <option value="{{ $provisionCategory->id }}" @selected($provisionCategoryId == $provisionCategory->id)>{{ $provisionCategory->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="provision" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Prestation</label>
        <select name="provision" id="provision" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="">Toutes</option>
            @foreach ($provisions as $provision)
            <option value="{{ $provision->id }}" @selected($provisionId == $provision->id)>{{ $provision->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center mb-4">
        <input name="amount" id="amount" type="checkbox" value="1" @checked($displayAmount) onchange="this.form.submit()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
        <label for="amount" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Afficher montant</label>
    </div>
    <div class="flex items-center mb-4">
        <input name="contacts" id="contacts" type="checkbox" value="1" @checked($displayContacts) onchange="this.form.submit()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
        <label for="contacts" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Afficher les contacts</label>
    </div>
</form>
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-4 py-2">
                        Client
                    </th>
                    @if ($displayContacts)
                    <th scope="col" class="px-4 py-2">
                        Contacts
                    </th>
                    @endif
                    {{--
                    <th scope="col" class="px-4 py-2">
                        Documents
                    </th>
                    <th scope="col" class="px-4 py-2">
                        Factures
                    </th>
                    --}}
                    <th scope="col" class="px-4 py-2">
                        Prestations
                    </th>
                    @if ($displayAmount)
                    <th scope="col" class="px-4 py-2">
                        Montant
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <div class="font-bold">
                            <span>{{ $client->name }}</span>
                            @if ($client->long_name)
                                <span class="text-gray-500" style="font-size: xx-small;">({{ $client->long_name }})</span>
                            @endif
                        </div>
                        {{--
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
                        --}}
                    </th>
                    @if ($displayContacts)
                    <td class="px-4 py-2">
                        @foreach ($client->contacts as $contact)
                        <div class="text-sm">
                            {{ $contact->name }}
                            @if ($contact->pivot->type)
                            <span class="text-xs">({{ $contact->pivot->type }})</span>
                            @endif
                            @if ($contact->email)
                            <span class="text-xs">{{ $contact->email }}</span>
                            @endif
                            @if ($contact->phone)
                            <span class="text-xs">{{ $contact->phone }}</span>
                            @endif
                        </div>
                        @endforeach
                    </td>
                    @endif
                    {{--
                    <td class="px-4 py-2">
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
                    <td class="px-4 py-2">
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
                    --}}
                    <td class="px-4 py-2">
                        <table>
                            @foreach ((
                                $provisionCategoryId ? $client->provisionElements->filter(function ($provisionElement) use($provisionCategoryId) {
                                    return $provisionElement->provision->category_id == $provisionCategoryId;
                                }) :
                                ($provisionId ? $client->provisionElements->where('provision_id', $provisionId) :
                                $client->provisionElements)
                                ) as $provision)
                            <tr class="border-b border-gray-200 last:border-0">
                                <td>
                                    <span
                                        style="--c-50:var(--{{ $provision->status->getColor() }}-50);--c-400:var(--{{ $provision->status->getColor() }}-400);--c-600:var(--{{ $provision->status->getColor() }}-600);"
                                        class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 me-1">
                                        {{ $provision->status->getLabel() }}
                                    </span>
                                </td>
                                <td><span class="me-2">{{ $provision->provision?->name }}</span></td>
                                <td><span class="me-2">{{ $provision->precision }}</td>
                                <td>{{ $provision->cost ? $provision->price->amount('c') : null }}</td>
                            </tr>
                            @endforeach
                        </table>
                    </td>
                    @if ($displayAmount)
                    <td class="px-4 py-2">
                        <span class="text-gray-900">{{ \App\Classes\Price::of($client->currentProvisionElementsAmount())->amount('pdf') }}</span>
                        <span class="text-gray-500" style="font-size: xx-small;">({{ \App\Classes\Price::of($client->currentProvisionElementsNetAmount())->amount('pdf') }})</span>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-layouts.app>
