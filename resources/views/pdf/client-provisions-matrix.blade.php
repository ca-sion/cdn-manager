<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Clients · Prestations (Matrice)</title>

    <style>
        @page {
            margin: 20px 20px 25px 40px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: small;
            color: #222222;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .document-vertical-line {
            position: fixed;
            top: 0;
            left: 0;
            margin-top: -50px;
            margin-left: -40px;
            width: 20px;
            height: 3000px;
            background-color: #BCDCF6;
        }
        p {
            margin-bottom: .4rem;
            margin-top: 0px;
        }
        .title {
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 2px;
            font-size: 14px;
        }
        .subtitle {
            font-weight: normal;
            margin-bottom: 10px;
            font-size: 9px;
            color: #555555;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .table thead {
            font-weight: bold;
            border-bottom: 1px solid #222222;
            background-color: #F4F6F8;
        }
        .table th, .table td {
            padding: 4px 3px;
            border-right: 1px solid #EAEAEA;
        }
        .table th:last-child, .table td:last-child {
            border-right: none;
        }
        .table tr {
            border-bottom: 1px solid #E3E3E3;
        }
        .table tbody tr:nth-child(even) {
            background-color: #FAFAFA;
        }
        .th-provision {
            height: 120px;
            vertical-align: bottom;
            padding: 4px 1px;
            text-align: left;
            width: 18px;
            max-width: 22px;
        }
        .th-provision-rotate {
            transform: rotate(-90deg);
            transform-origin: left bottom;
            width: 110px;
            display: block;
            font-size: 7px;
            font-weight: bold;
            line-height: 1;
            margin-left: 10px;
            white-space: nowrap;
        }
        .cell-check {
            text-align: center;
            font-weight: bold;
        }
        .cell-detail {
            font-size: 6.5px;
            display: block;
            color: #444444;
            font-weight: normal;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Clients · Prestations (Matrice)</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }} · {{ $activeProvisions->count() }} prestation(s) active(s)</div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <th style="text-align: left; min-width: 60px;">Catégorie</th>
                    <th style="text-align: left; min-width: 90px;">Client</th>
                    <th style="text-align: left; min-width: 50px;">Statut</th>
                    @foreach ($activeProvisions as $provision)
                        <th class="th-provision">
                            <span class="th-provision-rotate">{{ $provision->name }}</span>
                        </th>
                    @endforeach
                    <th style="text-align: right; min-width: 60px;">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr style="vertical-align: middle;">
                    <td>{{ $client->category?->name }}</td>
                    <td><strong>{{ str($client->name)->limit(26) }}</strong></td>
                    <td style="color: #666666;">
                        {{ $client->currentEngagement?->stage?->getLabel() }}
                    </td>

                    @foreach ($activeProvisions as $provision)
                        @php
                            $val = $matrix[$client->id][$provision->id] ?? null;
                        @endphp
                        <td class="cell-check">
                            @if ($val)
                                <span style="font-size: 8px;">{{ $val }}</span>
                            @endif
                        </td>
                    @endforeach

                    <td class="text-right">
                        @if ($client->advertiser_total > 0)
                            {{ (new App\Classes\Price())->generateFormatted($client->advertiser_total, 'pdf') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; border-top: 1.5px solid #222222; background-color: #F4F6F8;">
                    <td colspan="3" style="text-align: right">Total</td>
                    @foreach ($activeProvisions as $provision)
                        @php
                            $count = $clients->filter(fn($c) => !empty($matrix[$c->id][$provision->id]))->count();
                        @endphp
                        <td class="text-center" style="font-size: 7px; color: #555555;">
                            {{ $count > 0 ? $count : '' }}
                        </td>
                    @endforeach
                    <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</td>
                </tr>
            </tfoot>
        </table>

    </div>
    <!-- container -->

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Course de Noël et Trail des Châteaux · Rapport Matrice · État : '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
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
