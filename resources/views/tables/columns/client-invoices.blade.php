@php
//
@endphp
<div>
    <table class="my-2">
        @foreach ($record->invoices as $invoice)
        <div>
            <a href="{{ $invoice->link }}" target="_blank">📄</a>
            <a href="/admin/invoices/{{ $invoice->id }}/edit">{{ $invoice->number }}</a>
            ({{ $invoice->status->getLabel() }})
            @if ($invoice->paid_on)
                ✅
            @endif
        </div>
        @endforeach
    </table>
</div>
