<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $client->name }}</title>
    <link href="https://fonts.cdnfonts.com/css/dejavu-sans" rel="stylesheet">

    <style>

        @page {
            margin: 50px 50px 75px 50px;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: small;
            color:#222222
        }
        .dejavu {
            font-family: 'DejaVu Sans', sans-serif;
        }
        .container {
            max-width: 620px;
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
        .edition {
            font-weight: bold;
            margin-top: 24px;
            margin-bottom: 6px;
        }
        .title {
            font-size: 24px;
            margin-top: 24px;
            margin-bottom: 6px;
        }
        .subtitle {
            font-weight: bold;
            margin-bottom: 4px;
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
        }
        .table-border-between td {
            padding: 4px 4px 4px 0px;
        }
        .table-border-between tr {
            border-bottom: 1px solid #E3E3E3;
        }
        .table-border-between tr:last-child
        {
            border-bottom: none;
        }
      </style>
</head>

<body>

    <x-pdf.header />

    <div class="edition">{{ $edition->year }} · {{ $edition->name }}</div>

    <div class="title">{{ $client->name }}</div>
    <div>{{ $client->long_name }}</div>
    <br>
    <br>

    <div class="container">

        <table class="table">
            <tr>
                <td width="50%">
                    <div style="text-wrap: nowrap;word-break: break-word;">
                        <div class="subtitle">Adresse</div>
                        @if ($client->name)
                            {{ $client->name }}<br>
                        @endif
                        @if ($client->address)
                            {{ $client->address }}<br>
                        @endif
                        @if ($client->address_extension)
                            {{ $client->address_extension }}<br>
                        @endif
                        @if ($client->postal_code || $client->locality)
                            {{ $client->postal_code }}
                            {{ $client->locality }}
                            <br>
                        @endif
                    </div>
                </td>
                <td width="50%">
                    <div style="text-wrap: nowrap;word-break: break-word;">
                        <div class="subtitle">Adresse de facturation</div>
                        @if ($client->name || $client->invoicing_name)
                            {{ $client->invoicing_name ?? $client->name }}<br>
                        @endif
                        @if ($client->address || $client->invoicing_address)
                            {{ $client->invoicing_address ?? $client->address }}<br>
                        @endif
                        @if ($client->address_extension || $client->invoicing_address_extension)
                            {{ $client->invoicing_address_extension ?? $client->address_extension }}<br>
                        @endif
                        @if ($client->postal_code || $client->locality || $client->invoicing_postal_code || $client->invoicing_locality)
                            {{ $client->invoicing_postal_code ?? $client->postal_code }}
                            {{ $client->invoicing_locality ?? $client->locality }}
                            <br>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        @if ($client->contacts->count() > 0)
        <br>
        <br>
        <div class="subtitle">Contacts</div>
        <table class="table table-border-between">
            @foreach ($client->contacts as $contact)
            <tr>
                <td>{{ $contact->name }}</td>
                <td>{{ $contact->pivot->type ? \App\Enums\ContactRoleEnum::from($contact->pivot->type)->getLabel() : null }}</td>
                <td class="text-sm">{{ $contact->email }}</td>
                <td>{{ $contact->phone }}</td>
                <td>{{ $contact->role }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        @if ($client->provisionElements->count() > 0)
        <br>
        <br>
        <table class="table table-border-between">
            <tr>
                <td class="subtitle">Prestations</td>
                <td></td>
                <td></td>
                <td align="right" class="text-sm">TVA</td>
                <td align="right" class="text-sm">Montant</td>
            </tr>
            @foreach ($client->provisionElements as $pe)
            <tr class="text-sm">
                <td valign="top">
                    <div>{{ $pe->provision->name }}</div>
                    @if ($pe->provision->description)
                        <div style="color: gray; font-size: xx-small;">{{ $pe->provision->description }}</div>
                    @endif
                </td>
                <td valign="top" class="text-xs">{{ $pe->status ? str($pe->status->getLabel())->limit(7, '.') : null }}</td>
                <td valign="top" class="text-xs">{{ str($pe->precision) }}</td>
                <td valign="top" align="right">
                    <div>{{ $pe->tax_rate ? $pe->price?->formatted_pdf_tax_amount : null }}</div>
                </td>
                <td valign="top" align="right">
                    <div>{{ $pe->cost ? $pe->price?->formatted_pdf_price : null }}</div>
                    <div style="color: gray; font-size: xx-small;">{{ $pe->cost && $pe->tax_rate ? $pe->price?->formatted_pdf_net_price : null }}</div>
                </td>
            </tr>
            @endforeach
        </table>
        @endif

    </div>
    <!-- container -->

</body>
</html>
