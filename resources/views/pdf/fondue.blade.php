<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Fondue</title>

    <style>
        @page {
            margin: 25px 20px 25px 45px;
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
            margin-left: -45px;
            width: 20px;
            height: 3000px;
            background-color: #BCDCF6;
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

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: xx-small;
        }

        .table thead {
            font-weight: bold;
            border-bottom: 1px solid #222222;
        }

        .table td {
            padding: 4px 6px 4px 0px;
        }

        .table tbody tr {
            border-bottom: 1px solid #E3E3E3;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tfoot {
            font-weight: bold;
            border-top: 1px solid #222222;
        }

        .nowrap {
            white-space: nowrap;
        }

        .handwrite-cell {
            width: 42px;
            border-bottom: 1px dotted #999999;
        }
    </style>
</head>

<body>

    <div class="document-vertical-line"></div>

    <div class="title">Fondue</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <td class="nowrap">Statut</td>
                    <td class="nowrap">Facturé</td>
                    <td class="nowrap">Catégorie</td>
                    <td>Client / Bénéficiaire</td>
                    <td>Contact</td>
                    <td class="nowrap">Tél.</td>
                    <td>Email</td>
                    <td>Prestation</td>
                    <td style="text-align: right;" class="nowrap">Nb.</td>
                    <td style="text-align: center; width: 42px;" class="nowrap">Nb. réel</td>
                    <td>Précision</td>
                    <td>Note</td>
                </tr>
            </thead>
            <tbody>
                @forelse ($provisions as $pe)
                    <tr style="vertical-align: top;">
                        <td class="nowrap">{{ $pe->status?->getLabel() }}</td>
                        <td class="nowrap">{{ $pe->resolved_invoiced }}</td>
                        <td class="nowrap">{{ $pe->resolved_category }}</td>
                        <td>{{ str($pe->resolved_recipient)->limit(24) }}</td>
                        <td>{{ str($pe->resolved_contact)->limit(20) }}</td>
                        <td class="nowrap">{{ $pe->resolved_phone }}</td>
                        <td style="max-width: 110px; word-wrap: break-word; overflow-wrap: break-word;">
                            @if ($pe->resolved_email)
                                {!! str_replace('@', '@<wbr>', e($pe->resolved_email)) !!}
                            @endif
                        </td>
                        <td>{{ $pe->provision?->name }}</td>
                        <td style="text-align: right;" class="nowrap">{{ $pe->numeric_indicator }}</td>
                        <td class="handwrite-cell"></td>
                        <td>{{ $pe->precision ?? $pe->textual_indicator }}</td>
                        <td>{{ str($pe->note)->limit(28) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">Aucune prestation fondue enregistrée pour cette édition.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" style="text-align: right;">Total ({{ $provisions->count() }} {{ $provisions->count() > 1 ? 'lignes' : 'ligne' }})</td>
                    <td style="text-align: right;" class="nowrap">{{ $totalQuantity }}</td>
                    <td class="handwrite-cell"></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    </div>

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
