<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Donateurs</title>

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
      </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Donateurs interclasse</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <td>Catégorie</td>
                    <td>Donateur</td>
                    <td>Prix/Don</td>
                    <td>Statut</td>
                    <td style="text-align: right">Montant</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->category?->name }}</td>
                    <td>{{ str($client->name)->limit(24) }}</td>
                    <td>
                        @foreach ($client->provisionElements as $pe)
                            {{ $pe->textual_indicator }}
                        @endforeach
                    </td>
                    <td>
                        @foreach ($client->provisionElements as $pe)
                            {{ $pe->status?->getLabel() }}
                        @endforeach
                    </td>
                    <td style="text-align: right">
                        @if ($client->donor_total > 0)
                            {{ (new App\Classes\Price())->generateFormatted($client->donor_total, 'pdf') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right"><strong>Total</strong></td>
                    <td style="text-align: right"><strong>{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</strong></td>
                </tr>
            </tfoot>
        </table>

    </div>
    <!-- container -->

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Course de Noël · Rapport · État : '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
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
