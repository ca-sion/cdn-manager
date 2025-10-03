<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Rapport Comparatif des Prestations</title>

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
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #ccc;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .table thead {
            font-weight: bold;
            border-bottom: 1px solid #222;
        }

        .table td {
            padding: 5px 4px;
            vertical-align: top;
        }

        .table tbody tr {
            border-bottom: 1px solid #E3E3E3;
        }

        .table tfoot {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .summary-table td {
            padding: 2px 8px;
            font-size: 11px;
        }
        .summary-table .label {
            font-weight: bold;
        }
        .summary-table .value {
            text-align: right;
        }

        .provisions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .provisions-list li {
            white-space: nowrap;
        }

    </style>
</head>

<body>
    <div class="document-vertical-line"></div>

    <div class="title">Rapport comparatif des prestations</div>
    <div class="subtitle">Comparaison entre l'édition <strong>{{ $referenceEdition->year }}</strong> et <strong>{{ $comparisonEdition->year }}</strong></div>

    <div class="container">

        <div class="break-avoid">
            <div class="section-title">Résumé Financier</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Total pour l'édition {{ $referenceEdition->year }}:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['reference_total'], 'pdf') }}</td>
                </tr>
                <tr>
                    <td class="label">Total pour l'édition {{ $comparisonEdition->year }}:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['comparison_total'], 'pdf') }}</td>
                </tr>
                <tr>
                    <td class="label">Montant des nouveaux clients:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['new'], 'pdf') }}</td>
                </tr>
                <tr>
                    <td class="label">Montant des clients perdus:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['lost'], 'pdf') }}</td>
                </tr>
                <tr>
                    <td class="label">Gain sur clients modifiés:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['modified_gain'], 'pdf') }}</td>
                </tr>
                <tr>
                    <td class="label">Perte sur clients modifiés:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['modified_loss'], 'pdf') }}</td>
                </tr>
                <tr class="text-bold">
                    <td class="label">Différence nette:</td>
                    <td class="value">{{ (new App\Classes\Price())->generateFormatted($comparisonData['totals']['reference_total'] - $comparisonData['totals']['comparison_total'], 'pdf') }}</td>
                </tr>
            </table>
        </div>

        @if($comparisonData['new']->isNotEmpty())
            <div class="section-title">Nouveaux Clients ({{ $comparisonData['new']->count() }})</div>
            <table class="table break-avoid">
                <thead>
                    <tr>
                        <td>Client</td>
                        <td>Prestations</td>
                        <td class="text-right">Montant {{ $referenceEdition->year }}</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparisonData['new'] as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>
                                <ul class="provisions-list">
                                    @foreach($client->diff_details['provisions'] as $pe)
                                        <li>{{ $pe->name }} ({{ $pe->quantity ?: 1 }})</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($client->diff_details['total'], 'pdf') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($comparisonData['lost']->isNotEmpty())
            <div class="section-title">Clients Perdus ({{ $comparisonData['lost']->count() }})</div>
            <table class="table break-avoid">
                <thead>
                    <tr>
                        <td>Client</td>
                        <td>Prestations</td>
                        <td class="text-right">Montant {{ $comparisonEdition->year }}</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparisonData['lost'] as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>
                                <ul class="provisions-list">
                                    @foreach($client->diff_details['provisions'] as $pe)
                                        <li>{{ $pe->name }} ({{ $pe->quantity ?: 1 }})</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($client->diff_details['total'], 'pdf') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($comparisonData['modified']->isNotEmpty())
            <div class="section-title">Clients Modifiés ({{ $comparisonData['modified']->count() }})</div>
            <table class="table">
                <thead>
                    <tr>
                        <td>Client</td>
                        <td>Prestations {{ $comparisonEdition->year }}</td>
                        <td class="text-right">Montant {{ $comparisonEdition->year }}</td>
                        <td>Prestations {{ $referenceEdition->year }}</td>
                        <td class="text-right">Montant {{ $referenceEdition->year }}</td>
                        <td class="text-right">Différence</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparisonData['modified'] as $client)
                        <tr class="break-avoid">
                            <td>{{ $client->name }}</td>
                            <td>
                                <ul class="provisions-list">
                                    @foreach($client->diff_details['comparison_provisions'] as $pe)
                                        <li>{{ $pe->name }} ({{ $pe->quantity ?: 1 }})</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($client->diff_details['comparison_total'], 'pdf') }}</td>
                            <td>
                                <ul class="provisions-list">
                                    @foreach($client->diff_details['reference_provisions'] as $pe)
                                        <li>{{ $pe->name }} ({{ $pe->quantity ?: 1 }})</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="text-right">{{ (new App\Classes\Price())->generateFormatted($client->diff_details['reference_total'], 'pdf') }}</td>
                            <td class="text-right text-bold">{{ (new App\Classes\Price())->generateFormatted($client->diff_details['diff'], 'pdf') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

    </div>

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
