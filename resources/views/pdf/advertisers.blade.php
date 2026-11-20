<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Advertisers</title>

    <style>

        @page {
            margin: 25px 20px 25px 45px;
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
            margin-left: -45px;
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
            padding: 4px 6px 4px 0px;
        }
        .table tr {
            border-bottom: 1px solid #E3E3E3;
        }
        .table tr:last-child
        {
            border-bottom: none;
        }
        .nowrap {
            white-space: nowrap;
        }
        .summary-section {
            margin-top: 16px;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid > tbody > tr > td {
            vertical-align: top;
            padding: 0;
        }
        .summary-grid > tbody > tr > td:first-child {
            width: 48%;
            padding-right: 14px;
        }
        .summary-grid > tbody > tr > td:last-child {
            width: 52%;
            padding-left: 14px;
        }
        .summary-title {
            font-weight: bold;
            font-size: x-small;
            margin-top: 10px;
            margin-bottom: 4px;
            color: #222222;
        }
        .summary-title:first-child {
            margin-top: 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: xx-small;
            margin-bottom: 8px;
        }
        .summary-table thead {
            font-weight: bold;
            border-bottom: 1px solid #222222;
        }
        .summary-table td {
            padding: 3px 4px 3px 0px;
        }
        .summary-table tbody tr {
            border-bottom: 1px solid #E3E3E3;
        }
        .summary-table tfoot {
            font-weight: bold;
            border-top: 1px solid #222222;
        }
      </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Annonceurs</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <td class="nowrap">Catégorie</td>
                    <td style="width: 45px;">Resp.</td>
                    <td>Client</td>
                    <td class="nowrap">Tél.</td>
                    <td>Contact</td>
                    <td>Email</td>
                    <td class="nowrap">Statut</td>
                    <td style="text-align: center;">Anc.</td>
                    <td>Prestations</td>
                    <td style="text-align: right" class="nowrap">Montant</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                <tr>
                    <td class="nowrap">{{ $client->category?->name }}</td>
                    <td style="width: 45px;">{{ $client->currentEngagement?->responsible }}</td>
                    <td>{{ str($client->name)->limit(22) }}</td>
                    <td class="nowrap">
                        {{ $client->phone }}
                    </td>
                    <td>
                        @foreach ($client->contacts->sortBy('order_column') as $contact)
                        @if ($loop->first)
                            {{ str($contact->name)->limit(20) }}
                        @endif
                        @endforeach
                    </td>
                    <td style="max-width: 115px; word-wrap: break-word; overflow-wrap: break-word;">
                        @if ($client->resolved_email)
                            {!! str_replace('@', '@<wbr>', e($client->resolved_email)) !!}
                        @endif
                    </td>
                    <td class="nowrap">
                        {{ $client->currentEngagement?->stage?->getLabel() }}
                    </td>
                    <td style="text-align: center;" class="nowrap">
                        @if ($client->had_previous_provisions)
                            Oui
                        @endif
                    </td>
                    <td>
                        @foreach ($client->provisionElements as $pe)
                            {{ $pe->name }}@if ($pe->cost) ({{ $pe->cost }})@endif
                            @if (! $loop->last)
                                ·
                            @endif
                        @endforeach
                    </td>
                    <td style="text-align: right" class="nowrap">
                        @if ($client->advertiser_total > 0)
                            {{ (new App\Classes\Price())->generateFormatted($client->advertiser_total, 'pdf') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="9" style="text-align: right"><strong>Total</strong></td>
                    <td style="text-align: right" class="nowrap"><strong>{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="summary-section break-avoid">
            <table class="summary-grid">
                <tbody>
                    <tr>
                        <td>
                            <div class="summary-title">Synthèse générale</div>
                            <table class="summary-table">
                                <tbody>
                                    <tr>
                                        <td>Nombre total d'annonceurs</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['total_clients'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Annonceurs avec prestation(s)</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['clients_with_provisions'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Annonceurs sans prestation</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['clients_without_provisions'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Nombre total de prestations</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['total_provisions'] }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total général</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($summary['grand_total'], 'pdf') }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="summary-title">Totaux par catégorie</div>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <td>Catégorie</td>
                                        <td style="text-align: right;" class="nowrap">Clients</td>
                                        <td style="text-align: right;" class="nowrap">Avec prest.</td>
                                        <td style="text-align: right;" class="nowrap">Montant</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($totalsByCategory as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $row['count'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $row['with_provisions_count'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($row['total'], 'pdf') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['total_clients'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['clients_with_provisions'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="summary-title">Totaux par statut</div>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <td>Statut</td>
                                        <td style="text-align: right;" class="nowrap">Clients</td>
                                        <td style="text-align: right;" class="nowrap">Avec prest.</td>
                                        <td style="text-align: right;" class="nowrap">Montant</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($totalsByStage as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $row['count'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $row['with_provisions_count'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($row['total'], 'pdf') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['total_clients'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['clients_with_provisions'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                        <td>
                            <div class="summary-title">Totaux par prestation</div>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <td>Prestation</td>
                                        <td style="text-align: right;" class="nowrap">Qté</td>
                                        <td style="text-align: right;" class="nowrap">Montant</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($totalsByProvision as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ $row['count'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($row['total'], 'pdf') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3">Aucune prestation enregistrée</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td style="text-align: right;" class="nowrap">{{ $summary['total_provisions'] }}</td>
                                        <td style="text-align: right;" class="nowrap">{{ (new App\Classes\Price())->generateFormatted($grandTotal, 'pdf') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
    <!-- container -->

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Course de Noël et Trail des Châteaux · Rapport · État : '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
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
