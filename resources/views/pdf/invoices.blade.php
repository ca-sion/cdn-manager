<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Liste des Factures</title>
    <style>
        @page { margin: 25px 25px 25px 50px; }
        body { font-family: 'Helvetica', sans-serif; font-size: small; color:#222222; }
        .container { width: 100%; margin:0 auto; }
        .document-vertical-line { position: fixed; top: 0; left: 0; margin-top: -50px; margin-left: -50px; width: 20px; height: 3000px; background-color: #BCDCF6; }
        p { margin-bottom: .4rem; margin-top: 0px; }
        .title { font-weight: bold; margin-top: 12px; margin-bottom: 2px; }
        .subtitle { font-weight: normal; margin-bottom: 6px; font-size: x-small; }
        .table { width: 100%; border-collapse:collapse; font-size: xx-small; }
        .table thead { font-weight: bold; border-bottom: 1px solid #222222; }
        .table td { padding: 4px 4px 4px 0px; }
        .table tr { border-bottom: 1px solid #E3E3E3; }
        .table tr:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="document-vertical-line"></div>
    <div class="title">Rapport des Factures Émises</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">
        <table class="table">
            <thead>
                <tr>
                    <td>N° Facture</td>
                    <td>Client / Raison Sociale</td>
                    <td>Date</td>
                    <td>Échéance</td>
                    <td>Statut</td>
                    <td style="text-align: right">Montant Total</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                <tr>
                    <td>#{{ $invoice->number }}</td>
                    <td>{{ $invoice->client?->name ?? $invoice->invoicing_company_name }}</td>
                    <td>{{ $invoice->created_at?->format('d.m.Y') }}</td>
                    <td>{{ $invoice->due_at?->format('d.m.Y') }}</td>
                    <td>
                        {{ is_object($invoice->status) && method_exists($invoice->status, 'getLabel') ? $invoice->status->getLabel() : (string) ($invoice->status?->value ?? $invoice->status) }}
                    </td>
                    <td style="text-align: right">
                        {{ number_format($invoice->total, 2, '.', "'") }} CHF
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right"><strong>Total général</strong></td>
                    <td style="text-align: right"><strong>{{ number_format($grandTotal, 2, '.', "'") }} CHF</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Course de Noël · Rapport des Factures · '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
            $font = $fontMetrics->get_font("sans-serif", "normal");
            $size = 6;
            $y = 10;
            $x = $pdf->get_width() - 20 - $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($x, $y, $text, $font, $size);

            $text = "{PAGE_NUM} / {PAGE_COUNT}";
            $font = $fontMetrics->get_font("sans-serif", "normal");
            $size = 6;
            $y = $pdf->get_height() - 20;
            $x = $pdf->get_width() + 40 - $fontMetrics->get_text_width($text, $font, $size);
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>
