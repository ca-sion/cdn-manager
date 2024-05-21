<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $invoice->title }}</title>
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
        .products-table {
            margin-top: 30px;
        }
        .products-table td {
            font-size: x-small;
            padding: 2px 4px;
        }
        .products-table thead {
            border-bottom: 1px solid black;
        }
        #qr-bill-currency { float: none !important; display: inline-block; }
        #qr-bill-amount { display: inline-block; }
        #qr-bill {
            position: absolute;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 306px;
            margin-left: -79px;
            z-index: 100;
            /*background-color: blanchedalmond;*/
            background-color: white;
            transform: scale(0.931);
        }
      </style>
</head>

<body>

    <x-pdf.header />

    <table width="100%">
        <tr>
            <td align="left" valign="top" style="max-width:8cm;padding-top: 2.5cm;padding-left: 1.1cm">
                <div style="text-wrap: nowrap;word-break: break-word;">
                    @if ($invoice->client->name || $invoice->client->invoicing_name)
                        {{ $invoice->client->invoicing_name ?? $invoice->client->name }}<br>
                    @endif
                    @if ($invoice->client->address || $invoice->client->invoicing_address)
                        {{ $invoice->client->invoicing_address ?? $invoice->client->address }}<br>
                    @endif
                    @if ($invoice->client->address_extension || $invoice->client->invoicing_address_extension)
                        {{ $invoice->client->invoicing_address_extension ?? $invoice->client->address_extension }}<br>
                    @endif
                    @if ($invoice->client->postal_code || $invoice->client->locality || $invoice->client->invoicing_postal_code || $invoice->client->invoicing_locality)
                        {{ $invoice->client->invoicing_postal_code ?? $invoice->client->postal_code }}
                        {{ $invoice->client->invoicing_locality ?? $invoice->client->locality }}
                        <br>
                    @endif
                </div>
            </td>
            <td align="left" style="min-width: 3cm;text-align: right;font-size: x-small;">
                <div style="margin-right: 10px;">
                    Numéro de facture<br>
                    @if ($invoice->date)
                    Date de facturation<br>
                    @endif
                    @if ($invoice->due_date)
                    Échéance<br>
                    @endif
                    Référence<br>
                    Adresse<br><br><br><br>
                    IBAN<br>
                    Numéro de TVA
                </div>
            </td>
            <td align="left" style="font-size: x-small;">
                {{ $invoice->number }}<br>
                @if ($invoice->date)
                    {{ \Carbon\CArbon::parse($invoice->date)->locale('fr_CH')->isoFormat('L') }}<br>
                @endif
                @if ($invoice->due_date)
                    {{ \Carbon\CArbon::parse($invoice->due_date)->locale('fr_CH')->isoFormat('L') }}<br>
                @endif
                {{ $invoice->reference ?? '-' }}<br>
                Centre athlétique de Sion -<br>
                Course de Noël<br>
                Case postale 4057<br>
                1950 Sion<br>
                CH63 0026 5265 6542 4140 D<br>
                CHE-329.493.754 TVA

            </td>
        </tr>
    </table>

    <div class="container">

        <br>

        <br>

        <h2 style="font-size: medium;">
            {{ $invoice->title }}
            @if ($invoice->edition?->name)
                · {{ $invoice->edition->name }}
            @endif
        </h2>

        <div style="font-size: x-small;">
            <p>Madame, Monsieur,</p>
            <p>Nous vous adressons la présente facture pour le versement du montant convenu.</p>

            @if ($invoice->client_reference)
            <table>
                <tr>
                    <td valign="top">Votre référence :</td>
                    <td valign="top">{!! $invoice->client_reference !!}</td>
                </tr>
            </table>
            @endif
        </div>

        <div style="font-size: x-small;">
            <p>Nous vous remercions chaleureusement pour votre généreux soutien, et vous prions d’agréer nos salutations les meilleures.</p>
            <p>Le Comité de la Course de Noël</p>
        </div>

        <table width="100%" class="products-table break-avoid">
            <thead>
                <td width="1%"></td>
                <td width="40%">Désignation</td>
                <td width="10%" align="right">Prix unit.</td>
                <td>Qu.</td>
                <td>TVA</td>
                <td width="20%" align="right">TVA (CHF)</td>
                <td width="20%" align="right">Prix net</td>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                <tr class="break-avoid">
                    <td>
                        {{ $item->position }}
                    </td>
                    <td>
                        {{ $item->name }}
                    </td>
                    <td align="right">
                        {{ $item->price->cost('npdf') }}
                    </td>
                    <td>
                        {{ $item->quantity }}
                    </td>
                    <td>
                        @if ($item->tax_rate)
                        {{ $item->tax_rate }}%
                        @endif
                    </td>
                    <td align="right">
                        @if ($item->price->tax_amount)
                        {{ $item->price->taxAmount('pdf') }}
                        @endif
                    </td>
                    <td align="right">
                        {{ $item->price->netAmount('pdf') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table width="100%" style="font-size: x-small;margin-top: 10px;;margin-bottom: 10px;">
            <tr>
                <td style="width: 70%;">

                </td>
                <td style="width: 30%;">
                    <table width="100%">
                        <tr>
                            <td align="right" style="width: 50%;">
                                TVA
                            </td>
                            <td align="right" style="width: 50%;">
                                {{ App\Classes\Price::of($invoice->total_tax)->amount('pdf') }}
                            </td>
                        </tr>
                        <tr>
                            <td align="right" style="width: 50%;">
                                Total
                            </td>
                            <td align="right" style="width: 50%;">
                                {{ App\Classes\Price::of($invoice->total)->amount('pdf') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    </div>
    <!-- container -->

    {!! $qrBillOutput !!}

</body>
</html>
