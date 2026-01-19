<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Rapport Financier</title>

    <style>

        @page {
            margin: 25px 25px 25px 50px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: small;
            color:#222222
        }
        .container {
            width: 100%;
            margin:0 auto;
        }
        .document-vertical-line {
            position: fixed;
            top: 0;
            left: 0;
            margin-top: -50px;
            margin-left: -50px;
            width: 20px;
            height: 3000px;
            background-color: #BCDCF6;
        }
        p {
            margin-bottom: .4rem;
            margin-top: 0px;
        }
        .break-avoid {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .title {
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 2px;
        }
        .subtitle {
            font-weight: normal;
            margin-bottom: 6px;
            font-size: x-small;
        }
        .section-title {
            font-weight: bold;
            font-size: small;
            margin-top: 20px;
            margin-bottom: 5px;
            border-bottom: 2px solid #222;
            padding-bottom: 2px;
        }
        .text-sm {
            font-size: x-small;
        }
        .text-xs {
            font-size: xx-small;
        }
        .table {
            width: 100%;
            border-collapse:collapse;
            font-size: xx-small;
        }
        .table thead {
            font-weight: bold;
            border-bottom: 1px solid #222222;
        }
        .table td {
            padding: 4px 4px 4px 0px;
        }
        .table tr {
            border-bottom: 1px solid #E3E3E3;
        }
        .table tr:last-child
        {
            border-bottom: none;
        }
        .category-header {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .category-total {
            border-top: 1px solid #999;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
      </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Rapport Financier</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">

        <!-- SECTION 1: Facturation par Catégorie -->
        <div class="section-title">1. Facturation par Catégorie de Client</div>
        <table class="table">
            <thead>
                <tr>
                    <td>Catégorie</td>
                    <td style="text-align: right">Montant Facturé</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoicedByCategory as $category => $amount)
                <tr>
                    <td>{{ $category }}</td>
                    <td style="text-align: right">{{ (new App\Classes\Price())->generateFormatted($amount, 'pdf') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total</td>
                    <td style="text-align: right">{{ (new App\Classes\Price())->generateFormatted($totalInvoiced, 'pdf') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- SECTION 2: Détail par Produits -->
        <div class="break-avoid">
            <div class="section-title">2. Détail par Produits Facturés</div>
            <table class="table">
                <thead>
                    <tr>
                        <td>Produit / Prestation</td>
                        <td style="text-align: right">Quantité</td>
                        <td style="text-align: right">Montant Total</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoicedByProduct as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td style="text-align: right">{{ $product['quantity'] }}</td>
                        <td style="text-align: right">{{ (new App\Classes\Price())->generateFormatted($product['total'], 'pdf') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td style="text-align: right">{{ (new App\Classes\Price())->generateFormatted($totalInvoiced, 'pdf') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- SECTION 3: Factures Impayées -->
        <div class="break-avoid">
            <div class="section-title">3. Factures à Encaisser (Impayées)</div>
            <table class="table">
                <thead>
                    <tr>
                        <td>Client</td>
                        <td>Numéro</td>
                        <td>Date</td>
                        <td>Statut</td>
                        <td style="text-align: right">Montant</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupedUnpaidInvoices as $categoryName => $invoices)
                    <tr class="category-header">
                        <td colspan="5">{{ $categoryName }}</td>
                    </tr>
                    @foreach ($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->client->name }}</td>
                        <td>{{ $invoice->number }}</td>
                        <td>{{ $invoice->date?->format('d.m.Y') ?? $invoice->created_at->format('d.m.Y') }}</td>
                        <td>
                            {{ $invoice->status->getLabel() }}
                        </td>
                        <td style="text-align: right">
                            {{ (new App\Classes\Price())->generateFormatted($invoice->total, 'pdf') }}
                        </td>
                    </tr>
                    @endforeach
                    <tr class="category-total">
                        <td colspan="4" style="text-align: right"><strong>Total {{ $categoryName }}</strong></td>
                        <td style="text-align: right">
                            <strong>{{ (new App\Classes\Price())->generateFormatted($invoices->sum(fn($i) => $i->total), 'pdf') }}</strong>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right">Total Impayé</td>
                        <td style="text-align: right">{{ (new App\Classes\Price())->generateFormatted($unpaidTotal, 'pdf') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Rapport Financier · '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
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