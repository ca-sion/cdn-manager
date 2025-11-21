<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Journal provisions</title>

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

    <div class="title">Journal · Prestations</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">

        <table class="table">
            <thead>
                <tr>
                    <td>Catégorie</td>
                    <td>Client</td>
                    <td>Statut</td>
                    <td>Prestation</td>
                    <td> </td>
                    <td> </td>
                    <td>M.</td>
                    <td> </td>
                </tr>
            </thead>
            <tbody>
                @foreach ($provisions as $pe)
                <tr style="vertical-align: start;">
                    <td>{{ $pe->recipient?->category?->name }}</td>
                    <td>{{ str($pe->recipient?->name)->limit(24) }}</td>
                    <td style="max-width: 100px;">
                        {{ $pe->recipient->currentEngagement?->stage?->getLabel() }}
                    </td>
                    <td>
                        @if ($pe->provision)
                            {{ $pe->provision?->name }}
                        @endif
                    </td>
                    <td>
                        @if ($pe->provision?->dimensions_indicator)
                            {{ $pe->provision?->dimensions_indicator }}
                        @endif
                        @if ($pe->provision->id == setting('advertiser_form_donation_provision') && $pe->textual_indicator)
                            {{ $pe->textual_indicator }}
                            @if ($pe->price?->cost)
                                · {{ $pe->price?->cost }} CHF
                            @endif
                        @endif
                    </td>
                    <td>
                        @if ($pe->status)
                            {{ $pe->status->getLabel() }}
                        @endif
                    </td>
                    <td>
                        @if ($pe->getMedia('*')->isNotEmpty())
                            X
                        @endif
                    </td>
                    <td>
                        @if ($pe->note)
                            {{ str($pe->note)->limit(25) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
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
