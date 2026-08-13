<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Contrat Coureur Élite - {{ $element?->first_name }} {{ $element?->last_name }}</title>
    <link href="https://fonts.cdnfonts.com/css/dejavu-sans" rel="stylesheet">

    <style>

        @page {
            margin: 50px 50px 75px 50px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: small;
            color: #222222;
        }
        .dejavu {
            font-family: 'DejaVu Sans', sans-serif;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
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
        .section-title {
            font-size: small;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1px solid #BCDCF6;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table td, .data-table th {
            font-size: x-small;
            padding: 5px 8px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table td.label, .data-table th.label {
            width: 35%;
            font-weight: bold;
            color: #475569;
            text-align: left;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: xx-small;
            font-weight: bold;
            vertical-align: text-bottom;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #334155;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>

    <x-pdf.header />

    <table width="100%">
        <tr>
            <td align="left" valign="top" style="max-width:8cm;padding-top: 2.5cm;padding-left: 1.1cm">
                <div style="text-wrap: nowrap;word-break: break-word;">
                    @if ($element?->first_name || $element?->last_name)
                        <strong>{{ $element->first_name }} {{ $element->last_name }}</strong><br>
                    @endif
                    @if ($element?->address)
                        {{ $element->address }}<br>
                    @endif
                    @if ($element?->address_extension)
                        {{ $element->address_extension }}<br>
                    @endif
                    @if ($element?->postal_code || $element?->locality)
                        {{ $element->postal_code }} {{ $element->locality }}<br>
                    @endif
                    @if ($element?->country)
                        {{ $element->country }}<br>
                    @endif
                </div>
            </td>
            <td align="left" style="min-width: 3cm;text-align: right;font-size: x-small;">
                <div style="margin-right: 10px;">
                    Dossier N°<br>
                    Date<br>
                    @if ($edition?->name || $edition?->year)
                    Édition<br>
                    @endif
                </div>
            </td>
            <td align="left" style="font-size: x-small;">
                {{ $registration->id }}<br>
                {{ date('d.m.Y') }}<br>
                @if ($edition?->name || $edition?->year)
                {{ $edition?->name ?? 'Édition '.$edition?->year }}<br>
                @endif
            </td>
        </tr>
    </table>

    <div class="container">

        <br>

        <h2 style="font-size: medium;">
            Contrat pour coureur Élite
            @if ($edition?->name)
                · {{ $edition->name }}
            @elseif ($edition?->year)
                · Édition {{ $edition->year }}
            @endif
        </h2>

        @if ($element)
            {{-- 1. Identité du Coureur --}}
            <div class="section-title">1. Identité du coureur</div>
            <table class="data-table break-avoid">
                <tr>
                    <td class="label">Prénom et nom</td>
                    <td><strong>{{ $element->last_name }} {{ $element->first_name }}</strong></td>
                </tr>
                @if ($element->birthdate || $element->gender)
                <tr>
                    <td class="label">Date de naissance / Genre</td>
                    <td>
                        @if ($element->birthdate)
                            {{ $element->birthdate->format('d.m.Y') }}
                        @endif
                        @if ($element->gender)
                            ({{ is_object($element->gender) ? $element->gender->value : $element->gender }})
                        @endif
                    </td>
                </tr>
                @endif
                @if ($element->nationality)
                <tr>
                    <td class="label">Nationalité</td>
                    <td>{{ $element->nationality }}</td>
                </tr>
                @endif
                @if ($element->email || $registration->contact_email)
                <tr>
                    <td class="label">Adresse email</td>
                    <td>{{ $element->email ?: $registration->contact_email }}</td>
                </tr>
                @endif
                @if ($element->team)
                <tr>
                    <td class="label">Club / Équipe</td>
                    <td>{{ $element->team }}</td>
                </tr>
                @endif
                @if ($element->address || $element->postal_code || $element->locality)
                <tr>
                    <td class="label">Adresse postale</td>
                    <td>
                        @if ($element->address)
                            {{ $element->address }}
                            @if ($element->address_extension)
                                <br>{{ $element->address_extension }}
                            @endif
                            <br>
                        @endif
                        @if ($element->postal_code || $element->locality)
                            {{ $element->postal_code }} {{ $element->locality }}
                        @endif
                        @if ($element->country)
                            ({{ $element->country }})
                        @endif
                    </td>
                </tr>
                @endif
            </table>

            {{-- 2. Engagement & Course --}}
            @if ($element->run?->name || $element->run_name || $element->bloc)
            <div class="section-title">2. Engagement et course</div>
            <table class="data-table break-avoid">
                @if ($element->run?->name || $element->run_name)
                <tr>
                    <td class="label">Course inscrite</td>
                    <td><strong>{{ $element->run?->name ?? $element->run_name }}</strong></td>
                </tr>
                @endif
                @if ($element->bloc)
                <tr>
                    <td class="label">Bloc de départ</td>
                    <td>{{ $element->bloc }}</td>
                </tr>
                @endif
            </table>
            @endif

            {{-- 3. Conditions Financières & Primes --}}
            @php
                $hasFreeRegistration = (bool) $element->has_free_registration_fee;
                $hasBonusStart = $element->has_bonus_start || ($element->bonus_start_amount && $element->bonus_start_amount > 0);
                $hasBonusRanking = $element->bonus_ranking_amount && $element->bonus_ranking_amount > 0;
                $hasBonusArrival = $element->bonus_arrival_amount && $element->bonus_arrival_amount > 0;
                $iban = $element->iban ?: $registration->payment_iban;
                $hasFinancialPayout = $hasBonusStart || $hasBonusRanking || $hasBonusArrival || (bool) $element->has_expense_reimbursement;
                $showIban = $hasFinancialPayout && !empty($iban);

                $showFinancialSection = $hasFreeRegistration || $hasBonusStart || $hasBonusRanking || $hasBonusArrival || $showIban;
            @endphp

            @if ($showFinancialSection)
            <div class="section-title">3. Conditions et primes</div>
            <table class="data-table break-avoid">
                @if ($hasFreeRegistration)
                <tr>
                    <td class="label">Frais d'inscription</td>
                    <td><span class="badge badge-success">Offerts / Pris en charge</span></td>
                </tr>
                @endif

                @if ($hasBonusStart)
                <tr>
                    <td class="label">Prime de départ</td>
                    <td>
                        @if ($element->bonus_start_amount && $element->bonus_start_amount > 0)
                            {{ number_format($element->bonus_start_amount, 2, '.', "'") }} CHF
                        @endif
                    </td>
                </tr>
                @endif

                @if ($hasBonusRanking)
                <tr>
                    <td class="label">Prime de classement</td>
                    <td>{{ number_format($element->bonus_ranking_amount, 2, '.', "'") }} CHF</td>
                </tr>
                @endif

                @if ($hasBonusArrival)
                <tr>
                    <td class="label">Prime d'arrivée</td>
                    <td>{{ number_format($element->bonus_arrival_amount, 2, '.', "'") }} CHF</td>
                </tr>
                @endif

                @if ($showIban)
                <tr>
                    <td class="label">IBAN de versement</td>
                    <td><strong>{{ $iban }}</strong></td>
                </tr>
                @endif
            </table>
            @endif

            {{-- 4. Hébergement & Défraiements --}}
            @php
                $hasAccommodation = (bool) $element->has_accommodation;
                $hasExpenseReimbursement = (bool) $element->has_expense_reimbursement;
                $showLogisticsSection = $hasAccommodation || $hasExpenseReimbursement;
            @endphp

            @if ($showLogisticsSection)
            <div class="section-title">4. Hébergement et défraiements</div>
            <table class="data-table break-avoid">
                @if ($hasAccommodation)
                <tr>
                    <td class="label">Hébergement</td>
                    <td>
                        <strong>Hôtel Elite, Avenue du Midi 6, 1950 Sion</strong>
                        <br>
                        @if ($element->accommodation_friday)Nuit du vendredi soir @endif
                        @if ($element->accommodation_saturday) — Nuit du samedi soir @endif
                        @if ($element->accommodation_precision)<br>{{ $element->accommodation_precision }} @endif
                    </td>
                </tr>
                @endif

                @if ($hasExpenseReimbursement)
                <tr>
                    <td class="label">Remboursement de frais</td>
                    <td>
                        <span class="badge badge-success">Pris en charge</span>
                        @if ($element->expense_reimbursement_precision) {{ $element->expense_reimbursement_precision }} @endif
                    </td>
                </tr>
                @endif
            </table>
            @endif

            {{-- Remarques s'il y en a --}}
            @if ($element->payment_note)
            <div class="section-title">Remarques</div>
            <div style="font-size: x-small; margin-bottom: 15px;">
                <p>{{ $element->payment_note }}</p>
            </div>
            @endif

        @endif

    </div>
    <!-- container -->

</body>
</html>

