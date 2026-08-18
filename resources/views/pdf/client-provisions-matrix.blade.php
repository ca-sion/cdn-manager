<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Clients · Prestations (Matrice)</title>

    <style>
        @page {
            margin: 15px 15px 20px 30px;
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
            margin-left: -30px;
            width: 15px;
            height: 3000px;
            background-color: #BCDCF6;
        }
        p {
            margin-bottom: .4rem;
            margin-top: 0px;
        }
        .title {
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 2px;
            font-size: 13px;
        }
        .subtitle {
            font-weight: normal;
            margin-bottom: 8px;
            font-size: 8.5px;
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
            padding: 3px 2px;
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
        .th-provision-horizontal {
            font-size: 7.5px;
            text-align: center;
            vertical-align: bottom;
            padding: 5px 3px;
            word-wrap: break-word;
        }
        .th-provision {
            height: 125px;
            width: 18px;
            min-width: 18px;
            max-width: 18px;
            vertical-align: bottom;
            padding: 3px 0px;
            text-align: left;
            position: relative;
        }
        .th-provision-rotate {
            position: absolute;
            bottom: 12px;
            left: 12px;
            transform: rotate(-90deg);
            transform-origin: left bottom;
            width: 105px;
            font-size: 6.8px;
            font-weight: bold;
            line-height: 1.1;
            white-space: nowrap;
        }
        .cell-check {
            text-align: center;
            font-weight: bold;
            width: 18px;
            max-width: 18px;
        }
        .text-right {
            text-align: right;
            white-space: nowrap;
        }
        .text-nowrap {
            white-space: nowrap;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Clients · Prestations (Matrice)</div>
    <div class="subtitle">
        {{ $edition->year }} · {{ $edition->name }} · {{ $activeProvisions->count() }} prestation(s) active(s)
        @if (isset($selectedCategories) && $selectedCategories->isNotEmpty())
            · Catégorie(s) : {{ $selectedCategories->implode(', ') }}
        @endif
    </div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <th class="text-nowrap" style="text-align: left;">Catégorie</th>
                    <th class="text-nowrap" style="text-align: left;">Client</th>
                    <th class="text-nowrap" style="text-align: left;">Statut</th>
                    @foreach ($activeProvisions as $provision)
                        @if ($activeProvisions->count() <= 10)
                            <th class="th-provision-horizontal">
                                {{ str($provision->name)->limit(32) }}
                            </th>
                        @else
                            <th class="th-provision">
                                <span class="th-provision-rotate">{{ str($provision->name)->limit(32) }}</span>
                            </th>
                        @endif
                    @endforeach
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr style="vertical-align: middle;">
                    <td class="text-nowrap">{{ str($client->category?->name)->limit(18) }}</td>
                    <td class="text-nowrap"><strong>{{ str($client->name)->limit(28) }}</strong></td>
                    <td class="text-nowrap" style="color: #666666;">
                        {{ str($client->currentEngagement?->stage?->getLabel())->limit(14) }}
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
                            {{ (new App\Classes\Price())->generateFormatted($client->advertiser_total, 'npdf') }}
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
                    <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'npdf') }}</td>
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
