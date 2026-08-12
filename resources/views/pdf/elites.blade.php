<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Liste des Coureurs Élite</title>
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
    <div class="title">Rapport des Coureurs Élite</div>
    <div class="subtitle">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="container">
        <table class="table">
            <thead>
                <tr>
                    <td>Nom & Prénom</td>
                    <td>Sexe</td>
                    <td>Naissance</td>
                    <td>Nationalité</td>
                    <td>Course</td>
                    <td>IBAN</td>
                    <td>Hébergement</td>
                    <td style="text-align: right">Prime Départ</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($elements as $el)
                <tr>
                    <td><strong>{{ $el->last_name }}</strong> {{ $el->first_name }}</td>
                    <td>{{ is_object($el->gender) ? $el->gender->value : $el->gender }}</td>
                    <td>{{ $el->birthdate?->format('d.m.Y') }}</td>
                    <td>{{ $el->nationality }}</td>
                    <td>{{ $el->run?->name ?? $el->run_name }}</td>
                    <td>{{ $el->iban ?: $el->runRegistration?->payment_iban }}</td>
                    <td>
                        @if ($el->has_accommodation)
                            Oui ({{ $el->accommodation_friday ? 'Ven ' : '' }}{{ $el->accommodation_saturday ? 'Sam' : '' }})
                        @else
                            Non
                        @endif
                    </td>
                    <td style="text-align: right">
                        @if ($el->bonus_start_amount)
                            {{ number_format($el->bonus_start_amount, 2, '.', "'") }} CHF
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" style="text-align: right"><strong>Total Primes Départ</strong></td>
                    <td style="text-align: right"><strong>{{ number_format($totalStartBonus, 2, '.', "'") }} CHF</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = html_entity_decode('Course de Noël · Liste des Coureurs Élite · '.now()->locale('fr_CH')->isoFormat('L'), ENT_QUOTES, 'UTF-8');
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
