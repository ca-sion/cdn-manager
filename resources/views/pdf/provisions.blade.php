<x-layouts.app>
<form action="" method="GET" class="flex gap-4 m-4">
    <div>
        <label for="clientCategory" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catégorie</label>
        <select name="client_category" id="clientCategory" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option value="">Toutes</option>
            @foreach ($clientCategories as $category)
            <option value="{{ $category->id }}" @selected($clientCategoryId == $category->id)>{{ $category->name }}</option>
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
            @foreach ($provisionsList as $provision)
            <option value="{{ $provision->id }}" @selected($provisionId == $provision->id)>{{ $provision->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center mb-4">
        <input name="client" id="client" type="checkbox" value="1" @checked($displayClient) onchange="this.form.submit()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
        <label for="client" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Afficher le client</label>
    </div>
    <div class="flex items-center mb-4">
        <input name="amount" id="amount" type="checkbox" value="1" @checked($displayAmount) onchange="this.form.submit()" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
        <label for="amount" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Afficher montant</label>
    </div>
</form>
    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    @if ($displayClient)
                    <th scope="col" class="px-4 py-2">
                        Client
                    </th>
                    @endif
                    <th scope="col" class="px-4 py-2">
                        Statuts
                    </th>
                    <th scope="col" class="px-4 py-2">
                        Prestations
                    </th>
                    <th scope="col" class="px-4 py-2">
                         
                    </th>
                    @if ($displayAmount)
                    <th scope="col" class="px-4 py-2">
                        Montant
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($provisions as $provision)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    @if ($displayClient)
                    <th scope="row" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        <div class="font-bold">
                            <span>{{ optional($provision->recipient)->name }}</span>
                            @if (optional($provision->recipient)->long_name)
                                <span class="text-gray-500" style="font-size: xx-small;">({{ optional($provision->recipient)->long_name }})</span>
                            @endif
                        </div>
                    </th>
                    @endif
                    <td class="px-4 py-2">
                        <div class="w-[90px]">
                            <span
                                style="--c-50:var(--{{ $provision->status->getColor() }}-50);--c-400:var(--{{ $provision->status->getColor() }}-400);--c-600:var(--{{ $provision->status->getColor() }}-600);"
                                class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 me-1">
                                {{ $provision->status->getLabel() }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        {{ $provision->name }}
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex">
                            @if ($provision->precision)
                            <div class="min-w-[80px] max-w-[200px]"><span class="me-2">{{ $provision->precision }}</span></div>
                            @endif
                            @if ($provision->numeric_indicator)
                            <div class="mw-[20px]"><span class="me-2">{{ $provision->numeric_indicator }}</span></div>
                            @endif
                            @if ($provision->textual_indicator)
                            <div class="min-w-[80px] max-w-[200px]"><span class="me-2">{{ $provision->textual_indicator }}</span></div>
                            @endif
                            @if ($provision->goods_to_be_delivered)
                            <div class="min-w-[80px] max-w-[120px]"><span class="me-2">{{ $provision->goods_to_be_delivered }}</span></div>
                            @endif
                            @if ($provision->contact)
                            <div class="w-[120px]"><span class="me-2">{{ $provision->contact?->name }}</span></div>
                            @endif
                            @if ($provision->contact_text)
                            <div class="w-[200px]"><span class="me-2">{{ $provision->contact_text }}</span></div>
                            @endif
                            @if ($provision->contact_location)
                            <div class="w-[200px]"><span class="me-2">{{ $provision->contact_location }}</span></div>
                            @endif
                            @if ($provision->contact_date)
                            <div class="w-[80px]"><span class="me-2">{{ \Carbon\Carbon::parse($provision->contact_date)->locale('fr_CH')->isoFormat('L') }}</span></div>
                            @endif
                            @if ($provision->contact_time)
                            <div class="w-[40px]"><span class="me-2">{{ \Carbon\Carbon::parse($provision->contact_time)->locale('fr_CH')->isoFormat('LT') }}</span></div>
                            @endif
                            @if ($provision->media_status)
                            <div class="w-[60px]"><span class="me-2">{{ $provision->media_status }}</span></div>
                            @endif
                            @if ($provision->responsible)
                            <div class="w-[120px]"><span class="me-2">{{ $provision->responsible }}</span></div>
                            @endif
                            @if ($provision->dicastry)
                            <div class="w-[100px]"><span class="me-2">{{ $provision->dicastry?->name }}</span></div>
                            @endif
                            @if ($provision->tracking_status)
                            <div class="w-[80px]"><span class="me-2">{{ $provision->tracking_status }}</span></div>
                            @endif
                            @if ($provision->tracking_date)
                            <div class="w-[80px]"><span class="me-2">{{ $provision->tracking_date }}</span></div>
                            @endif
                            @if ($provision->accreditation_type)
                            <div class="w-[80px]"><span class="me-2">{{ $provision->accreditation_type }}</span></div>
                            @endif
                            @if ($provision->vip_category)
                            <div class="w-[80px]"><span class="me-2">{{ $provision->vip_category }}</span></div>
                            @endif
                            @if ($provision->vip_invitation_number)
                            <div class="w-[20px]"><span class="me-2">{{ $provision->vip_invitation_number }}</span></div>
                            @endif
                            @if ($provision->vip_response_status)
                            <div class="w-[40px]"><span class="me-2">{{ $provision->vip_response_status }}</span></div>
                            @endif
                            @if ($provision->vip_guests)
                            <div class="w-[200px]"><span class="me-2">{{ $provision->vip_guests }}</span></div>
                            @endif
                            @if ($provision->note)
                            <div><span class="me-2">{{ $provision->note }}</span></div>
                            @endif
                        </div>
                    </td>
                    @if ($displayAmount)
                    <td class="px-4 py-2">
                        @if ($provision->price)
                        <span class="text-gray-900">{{ \App\Classes\Price::of($provision->price->amount)->amount('pdf') }}</span>
                        <span class="text-gray-500" style="font-size: xx-small;">({{ \App\Classes\Price::of($provision->price->net_amount)->amount('pdf') }})</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach

                @if ($displayAmount)
                <tr class="border-t-2 border-gray-600">
                    @if ($displayClient)
                    <td></td>
                    @endif
                    <td></td>
                    <td></td>
                    <td class="text-right">Total</td>
                    <td class="px-4 py-2">
                        <span class="text-gray-900">{{ \App\Classes\Price::of($amountSum)->amount('pdf') }}</span>
                        <span class="text-gray-500" style="font-size: xx-small;">({{ \App\Classes\Price::of($netAmountSum)->amount('pdf') }})</span>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <style>
        @media print {
            /*
            .text-sm {
                font-size: 60%;
            }
            .text-xs {
                font-size: 50%;
            }
            .px-4 {
                padding-left: 4px;
                padding-right: 4px;
            }
            py-2 {
                padding-top: 2px;
                padding-bottom: 2px;
            }
            */
        }
    </style>

</x-layouts.app>
