@php
    $record = $getRecord();
    $provisions = $record->provisionElements;
@endphp
<div>
    <table class="my-2">
        @foreach ($provisions as $provision)
        <tr class="text-sm leading-6 text-gray-950 dark:text-white">
            <td class="px-2">
                <span style="--c-50:var(--{{ $provision->status->getColor() }}-50);--c-400:var(--{{ $provision->status->getColor() }}-400);--c-600:var(--{{ $provision->status->getColor() }}-600);" class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-{{ $provision->status->getColor() }}">
                    {{ $provision->status->getLabel() }}
                </span>

            </td>
            <td class="px-2">{{ $provision->provision?->name }}</td>
            <td class="px-2">{{ $provision->precision }}</td>
            <td class="px-2">
                @if ($provision->cost)
                    {{ \App\Services\PricingService::format(\App\Services\PricingService::applyQuantity(\App\Services\PricingService::calculateCostPrice($provision->cost, $provision->tax_rate, $provision->include_vat), $provision->quantity)) }}
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
