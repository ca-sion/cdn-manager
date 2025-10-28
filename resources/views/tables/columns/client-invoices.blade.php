@php
//
@endphp
<div>
    <table class="my-2">
        @foreach ($record->currentInvoices as $invoice)
        <div>
            <a href="{{ $invoice->link }}" target="_blank">
                <x-heroicon-o-document class="inline h-3" />
            </a>
            <a href="/admin/invoices/{{ $invoice->id }}/edit" title="{{ $invoice->number }}">
                <x-heroicon-o-pencil-square class="inline h-3" />
            </a>
            <span style="--c-50:var(--{{ $invoice->status->getColor() }}-50);--c-400:var(--{{ $invoice->status->getColor() }}-400);--c-600:var(--{{ $invoice->status->getColor() }}-600);" class="inline-flex fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-{{ $invoice->status->getColor() }}">
                {{ $invoice->status->getLabel() }}
            </span>
            @if ($invoice->paid_on)
            <x-heroicon-o-check-circle class="inline h-5" style="color: green;" title="{{ $invoice->paid_on }}"/>
            @endif
        </div>
        @endforeach
    </table>
</div>
