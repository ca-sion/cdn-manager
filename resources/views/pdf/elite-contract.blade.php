<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat Coureur Élite - {{ $element->first_name ?? '' }} {{ $element->last_name ?? '' }}</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #1a202c;
            line-height: 1.5;
            margin: 20px;
        }
        .header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 13px;
            color: #64748b;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            background-color: #f1f5f9;
            padding: 6px 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #2563eb;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th, table.data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            width: 35%;
            font-weight: bold;
            color: #475569;
            background-color: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            background-color: #e2e8f0;
            color: #334155;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            padding-top: 10px;
        }
        .signature-box {
            border: 1px dashed #cbd5e1;
            height: 90px;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">CONTRAT COUREUR ÉLITE</div>
                    <div class="subtitle">Course de Noël Sion — Édition {{ $edition->year ?? date('Y') }}</div>
                </td>
                <td style="text-align: right;">
                    <strong>Dossier N° :</strong> #{{ $registration->id }}<br>
                    <strong>Date :</strong> {{ date('d.m.Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">1. Identité du Coureur Élite</div>
    <table class="data-table">
        <tr>
            <th>Nom & Prénom</th>
            <td><strong>{{ $element->last_name ?? '' }} {{ $element->first_name ?? '' }}</strong></td>
        </tr>
        <tr>
            <th>Date de naissance / Genre</th>
            <td>
                {{ $element->birthdate ? $element->birthdate->format('d.m.Y') : '-' }} 
                ({{ is_object($element->gender) ? $element->gender->value : ($element->gender ?? '-') }})
            </td>
        </tr>
        <tr>
            <th>Nationalité</th>
            <td>{{ $element->nationality ?: 'Switzerland' }}</td>
        </tr>
        <tr>
            <th>Adresse email</th>
            <td>{{ $element->email ?: ($registration->contact_email ?: '-') }}</td>
        </tr>
        <tr>
            <th>Adresse postale</th>
            <td>
                {{ $element->address ?? '-' }} 
                {{ $element->address_extension ? '('.$element->address_extension.')' : '' }}<br>
                {{ $element->postal_code ?? '' }} {{ $element->locality ?? '' }} ({{ $element->country ?? 'SUI' }})
            </td>
        </tr>
    </table>

    <div class="section-title">2. Engagement & Course</div>
    <table class="data-table">
        <tr>
            <th>Course inscrite</th>
            <td><strong>{{ $element->run?->name ?? ($element->run_name ?? 'Course Élite') }}</strong></td>
        </tr>
        <tr>
            <th>Bloc de départ</th>
            <td>{{ $element->bloc ?: 'Attribué par l\'organisation' }}</td>
        </tr>
    </table>

    <div class="section-title">3. Conditions Financières & Primes</div>
    <table class="data-table">
        <tr>
            <th>Frais d'inscription</th>
            <td>
                @if($element->has_free_registration_fee)
                    <span class="badge badge-success">Offerts / Pris en charge</span>
                @else
                    <span class="badge">Standard</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>Prime de départ</th>
            <td>
                @if($element->has_bonus_start)
                    <span class="badge badge-success">Oui</span> {{ $element->bonus_start_amount ? '('.number_format($element->bonus_start_amount, 2).' CHF)' : '' }}
                @else
                    Non
                @endif
            </td>
        </tr>
        <tr>
            <th>Prime de classement</th>
            <td>{{ $element->bonus_ranking_amount ? number_format($element->bonus_ranking_amount, 2).' CHF' : 'Selon grille officielle' }}</td>
        </tr>
        <tr>
            <th>Prime d'arrivée</th>
            <td>{{ $element->bonus_arrival_amount ? number_format($element->bonus_arrival_amount, 2).' CHF' : '-' }}</td>
        </tr>
        <tr>
            <th>IBAN de versement des primes</th>
            <td><strong>{{ $element->iban ?: ($registration->payment_iban ?: 'À communiquer') }}</strong></td>
        </tr>
    </table>

    <div class="section-title">4. Hébergement & Defraiements</div>
    <table class="data-table">
        <tr>
            <th>Hébergement pris en charge</th>
            <td>
                @if($element->has_accommodation)
                    <span class="badge badge-success">Oui</span>
                    @if($element->accommodation_friday) (Nuit du vendredi) @endif
                    @if($element->accommodation_saturday) (Nuit du samedi) @endif
                    @if($element->accommodation_precision) — {{ $element->accommodation_precision }} @endif
                @else
                    Non
                @endif
            </td>
        </tr>
        <tr>
            <th>Remboursement de frais</th>
            <td>
                @if($element->has_expense_reimbursement)
                    <span class="badge badge-success">Oui</span> {{ $element->expense_reimbursement_precision ? '('.$element->expense_reimbursement_precision.')' : '' }}
                @else
                    Non
                @endif
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <strong>Pour l'Organisation :</strong><br>
                Fait à Sion, le {{ date('d.m.Y') }}
                <div class="signature-box"></div>
            </td>
            <td>
                <strong>Le Coureur Élite :</strong><br>
                Lu et approuvé
                <div class="signature-box"></div>
            </td>
        </tr>
    </table>

</body>
</html>
