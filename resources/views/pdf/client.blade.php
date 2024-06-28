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
            max-width: 800px;
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

        @if ($client->currentProvisionElements->count() > 0)
        <br>
        <br>
        <table class="table table-border-between">
            <tr>
                <td width="45%" class="subtitle">Prestations</td>
                <td width="12%"></td>
                <td></td>
                <td align="right" class="text-sm" width="8%">TVA</td>
                <td align="right" class="text-sm" width="15%">Montant</td>
            </tr>
            @foreach ($client->currentProvisionElements as $pe)
            <tr class="text-sm">
                <td valign="top">
                    <div>{{ $pe->provision->name }}</div>
                    <div style="color: gray; font-size: xx-small;">
                        @if ($pe->provision->description)
                            {{ $pe->provision->description }}
                        @endif
                        @if ($pe->note)
                            ({{ str($pe->note) }})
                        @endif
                        @if ($pe->due_date)
                            Délai : {{ str($pe->due_date->locale('fr_CH')->isoFormat('L')) }}
                        @endif
                    </div>
                </td>
                <td valign="top" class="text-xs">{{-- $pe->status ? $pe->status->getLabel() : null --}}</td>
                <td valign="top" class="text-xs">
                    @if ($pe->precision)
                        {{ str($pe->precision) }}
                    @endif
                    @if ($pe->textual_indicator)
                        {{ str($pe->textual_indicator) }}
                    @endif
                    @if ($pe->numeric_indicator)
                        ({{ str($pe->numeric_indicator) }})
                    @endif
                    @if ($pe->goods_to_be_delivered)
                        {{ str($pe->goods_to_be_delivered) }}
                    @endif
                    @if ($pe->vip_invitation_number)
                        Invitations : {{ str($pe->vip_invitation_number) }}
                    @endif
                    @if ($pe->vip_category)
                        <!--[{{ str($pe->vip_category) }}]-->
                    @endif
                    @if ($pe->accreditation_type)
                        <!--[{{ str($pe->accreditation_type) }}]-->
                    @endif
                    @if ($pe->media_status)
                        <!--[{{ str($pe->media_status) }}]-_>
                    @endif
                    @if ($pe->tracking_status)
                        <!--[{{ str($pe->tracking_status) }}]-->
                    @endif
                    @if ($pe->tracking_date)
                        Suivi le {{ str($pe->tracking_date->locale('fr_CH')->isoFormat('L')) }}
                    @endif
                    @if ($pe->contact_date)
                        Rendez-vous : {{ str($pe->contact_date->locale('fr_CH')->isoFormat('L')) }}
                    @endif
                    @if ($pe->contact_time)
                        {{ str($pe->contact_time) }}
                    @endif
                    @if ($pe->contact_location)
                        {{ str($pe->contact_location) }}
                    @endif
                    @if ($pe->contact_text)
                        Contact : {{ str($pe->contact_text) }}
                    @endif
                    @if ($pe->contact_id)
                        Contact : {{ str($pe->contact?->name) }}
                    @endif
                </td>
                <td valign="top" align="right">
                    <div>{{ $pe->tax_rate ? $pe->price?->taxAmount('npdf') : null }}</div>
                </td>
                <td valign="top" align="right">
                    <div>{{ $pe->cost ? $pe->price?->amount('pdf') : null }}</div>
                    <div style="color: gray; font-size: xx-small;">{{ $pe->cost && $pe->tax_rate ? $pe->price?->netAmount('pdf') : null }}</div>
                </td>
            </tr>
            @endforeach
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
                                {{ App\Classes\Price::of($client->currentProvisionElementsTaxAmount())->amount('pdf') }}
                            </td>
                        </tr>
                        <tr>
                            <td align="right" style="width: 50%;">
                                Total
                            </td>
                            <td align="right" style="width: 50%;">
                                {{ App\Classes\Price::of($client->currentProvisionElementsAmount())->amount('pdf') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        @endif

    </div>
    <!-- container -->

</body>
</html>
