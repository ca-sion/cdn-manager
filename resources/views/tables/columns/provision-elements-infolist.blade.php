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
                <span style="font-size: xx-small;">
                @if ($provision->textual_indicator)
                    - {{ str($provision->textual_indicator) }}
                @endif
                @if ($provision->numeric_indicator)
                    ({{ str($provision->numeric_indicator) }})
                @endif
                @if ($provision->goods_to_be_delivered)
                    - {{ str($provision->goods_to_be_delivered) }}
                @endif
                @if ($provision->vip_invitation_number)
                    - Nb. invit. : {{ str($provision->vip_invitation_number) }}
                @endif
                @if ($provision->vip_category)
                <!--[{{ str($provision->vip_category) }}]-->
                @endif
                @if ($provision->accreditation_type)
                    <!--[{{ str($provision->accreditation_type) }}]-->
                @endif
                @if ($provision->media_status)
                    <!--[{{ str($provision->media_status?->getLabel()) }}]-->
                @endif
                @if ($provision->tracking_status)
                    <!--[{{ str($provision->tracking_status) }}]-->
                @endif
                @if ($provision->tracking_date)
                    - Suivi le {{ str($provision->tracking_date->locale('fr_CH')->isoFormat('L')) }}
                @endif
                @if ($provision->contact_date)
                    - Rendez-vous : {{ str($provision->contact_date->locale('fr_CH')->isoFormat('L')) }}
                @endif
                @if ($provision->contact_time)
                    - {{ str($provision->contact_time) }}
                @endif
                @if ($provision->contact_location)
                    - {{ str($provision->contact_location) }}
                @endif
                @if ($provision->contact_text)
                    - Contact : {{ str($provision->contact_text) }}
                @endif
                @if ($provision->contact_id)
                    - Contact : {{ str($provision->contact?->name) }}
                @endif
                </span>
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
