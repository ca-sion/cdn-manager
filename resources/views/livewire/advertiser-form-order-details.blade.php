<div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
    <!--<h2 class="text-xl font-semibold text-gray-900 dark:text-white">Résumé</h2>-->
    <div class="flex flex-col gap-4">
        @foreach ($provisions as $provision)
        <div class="">
            <p class="text-base font-bold text-gray-900 dark:text-white">{{ $provision->description }}</p>
            <ul class="ms-2">
                    @if ($provision->dimensions_indicator)
                    <li>Dimensions : {{ $provision->dimensions_indicator }}</li>
                    @endif
                    @if ($provision->format_indicator)
                    <li>Format : {{ $provision->format_indicator }}</li>
                    @endif
                    @if ($provision->due_date_indicator && $provision->contact_indicator)
                    <li>Transmettre avant le : {{ $provision->due_date_indicator }} à {{ $provision->contact_indicator }}</li>
                    @endif
            </ul>
        </div>
        @endforeach
        @if ($donnationProvisionAmount)
        <div class="">
            <p class="text-base font-bold text-gray-900 dark:text-white">Don de {{ $donnationProvisionAmount }}</p>
            <ul class="ms-2">
                    @if ($donnationProvisionMention)
                    <li>Mention : {{ $donnationProvisionMention }}</li>
                    @endif
            </ul>
        </div>
        @endif
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4 mt-4">
    <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Montant</h2>
        <div class="space-y-4">
            <div class="space-y-2">
            <dl class="flex items-center justify-between gap-4">
                <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Net</dt>
                <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $total_net }}</dd>
            </dl>

            <dl class="flex items-center justify-between gap-4">
                <dt class="text-base font-normal text-gray-500 dark:text-gray-400">TVA</dt>
                <dd class="text-base font-medium text-gray-900 dark:text-white">{{ $total_taxes }}</dd>
            </dl>
            </div>

            <dl class="flex items-center justify-between gap-4 border-t border-gray-200 pt-2 dark:border-gray-700">
            <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
            <dd class="text-base font-bold text-gray-900 dark:text-white">{{ $total }}</dd>
            </dl>
        </div>
    </div>

    <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Coordonnées</h2>
        <table class="w-full text-left">

            @if (data_get($data, 'name'))
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="text-base font-normal text-gray-500 dark:text-gray-400 md:w-[150px]">Annonceur</td>
                <td class="text-base font-medium text-gray-900 dark:text-white">
                    @if (data_get($data, 'name'))
                        {{ $data?->long_name ?? $data?->name }}<br>
                    @endif
                    @if (data_get($data, 'address'))
                        {{ $data?->address }}<br>
                    @endif
                    @if (data_get($data, 'postal_code'))
                        {{ $data?->postal_code }} {{ $data?->locality }}
                    @endif
                </td>
            </tr>
            @endif

            @if (data_get($data, 'contact.first_name'))
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="text-base font-normal text-gray-500 dark:text-gray-400 md:w-[150px]">Contact</td>
                <td class="text-base font-medium text-gray-900 dark:text-white">
                    {{ data_get($data, 'contact.first_name') }}
                    {{ data_get($data, 'contact.last_name') }}
                    @if (data_get($data, 'contact.email'))
                        ({{ $data?->contact?->email }})
                    @endif
                </td>
            </tr>
            @endif

            @if (data_get($data, 'invoicing_address'))
            <tr class="border-b border-gray-200 dark:border-gray-700">
                <td class="text-base font-normal text-gray-500 dark:text-gray-400 md:w-[150px]">Adresse de facturation</td>
                <td class="text-base font-medium text-gray-900 dark:text-white">
                    @if (data_get($data, 'name'))
                        {{ $data?->name }}<br>
                    @endif
                    @if (data_get($data, 'invoicing_address') || data_get($data, 'address'))
                        {{ $data?->invoicing_address ?? $data?->address }}<br>
                    @endif
                    @if (data_get($data, 'invoicing_address_extension'))
                        {{ $data?->invoicing_address_extension }}<br>
                    @endif
                    @if (data_get($data, 'invoicing_postal_code') || data_get($data, 'postal_code'))
                        {{ $data?->invoicing_postal_code ?? $data?->postal_code }} {{ $data?->invoicing_locality ?? $data?->locality }}<br>
                    @endif
                    @if (data_get($data, 'invoicing_email'))
                        {{ $data?->invoicing_email }}<br>
                    @endif
                </td>
            </tr>
            @endif

            @if (data_get($data, 'first_name') && data_get($data, 'last_name'))
            <tr class="">
                <td class="text-base font-normal text-gray-500 dark:text-gray-400 md:w-[150px]">Donateur</td>
                <td class="text-base font-medium text-gray-900 dark:text-white">
                    @if (data_get($data, 'first_name') && data_get($data, 'last_name'))
                        {{ data_get($data, 'first_name') }} {{ data_get($data, 'last_name') }}<br>
                    @endif
                    @if (data_get($data, 'email'))
                        {{ $data?->email }}<br>
                    @endif
                </td>
            </tr>
            @endif

        </table>
    </div>
</div>
