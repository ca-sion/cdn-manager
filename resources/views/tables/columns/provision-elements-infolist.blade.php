@php
    $record = $getRecord();
    $provisions = $record->provisionElements;
@endphp
<div>
    <table class="my-2">
        @foreach ($provisions as $provision)
        <tr class="text-sm leading-6 text-gray-950 dark:text-white border-b border-gray-200 last:border-0">
            <td class="px-2 py-1">
                <span style="--c-50:var(--{{ $provision->status->getColor() }}-50);--c-400:var(--{{ $provision->status->getColor() }}-400);--c-600:var(--{{ $provision->status->getColor() }}-600);" class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-{{ $provision->status->getColor() }}">
                    {{ $provision->status->getLabel() }}
                </span>

            </td>
            <td class="px-2">
                {{ $provision->provision?->name }}
                @if ($provision->precision)
                    - {{ $provision->precision }}
                @endif
            </td>
            <td class="px-2" style="font-size: xx-small;">
                @if ($provision->cost)
                    {{ $provision->price->amount('c') }}
                    <span style="color: gray;">({{ $provision->price->netAmount('c') }})</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
