<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Sprain\SwissQrBill\QrBill;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        // viewed_at logic
        $referer = request()->headers->get('referer');
        $host = request()->host();
        if (! str($referer)->contains($host)) {
            $invoice->viewed_at = now();
            $invoice->save();
        }

        return InvoiceService::generatePdf($invoice)->stream($invoice->number.'.pdf');
    }

    public function eml(Invoice $invoice)
    {
        $dueDate = Carbon::parse($invoice->due_date)->locale('fr_CH')->isoFormat('L');
        $invoiceLink = quoted_printable_encode($invoice->link);
        $recipient = $invoice->client?->invoicingContactEmail;
        $nowDate = date('D, d M Y H:i:s O');
        $editionYear = $invoice->edition?->year;

        $invoice->status = InvoiceStatusEnum::Sent;
        $invoice->save();

        $body = <<<MAIL
From: Course de =?utf-8?Q?No=C3=ABl?= <info@coursedenoel.ch>
To: $recipient
Subject: Course de Noël $editionYear - Facture (F$invoice->number)
MIME-Version: 1.0
Date: $nowDate
Message-ID: <23307f2fd117e41de1a18a7a135e95f1@coursedenoel.ch>
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Bonjour,<br>
  <br>
Nous vous adressons la présente facture pour le versement du montant convenu.<br>
Pour la visionner, cliquer sur le lien ci-après.<br>
  <ul>
    <li>Visionner la facture: $invoiceLink</li>
  </ul>
Vous pouvez la payer d'ici au $dueDate en utilisant le bulletin de versement (QR-facture) attaché.<br>
  <br>
Nous vous remercions chaleureusement pour votre généreux soutien.<br>
Je reste à votre disposition pour toutes questions ou remarques.<br>
  <br>
Meilleures salutations<br>
Michael Ravedoni, Administration<br>
Course de Noël<br>
MAIL;

        return response($body, 200, [
            'Content-Type' => 'message/rfc822',
        ]);
    }

    public function emlRelaunch(Invoice $invoice)
    {
        $date = $invoice->date->locale('fr_CH')->isoFormat('L');
        $invoiceLink = quoted_printable_encode($invoice->link);
        $recipient = $invoice->client?->invoicingContactEmail;
        $nowDate = date('D, d M Y H:i:s O');
        $editionYear = $invoice->edition?->year;

        $invoice->status = InvoiceStatusEnum::Relaunched;
        $invoice->save();

        $body = <<<MAIL
From: Course de =?utf-8?Q?No=C3=ABl?= <info@coursedenoel.ch>
To: $recipient
Subject: Course de Noël $editionYear - Facture (F$invoice->number) : rappel
MIME-Version: 1.0
Date: $nowDate
Message-ID: <23307f2fd117e41de1a18a7a135e95f1@coursedenoel.ch>
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Bonjour,<br>
<br>
Sauf erreur de notre part, le paiement de la facture F$invoice->number du $date ne nous est pas parvenu.<br>
Pour visionner votre facture, cliquer sur le lien ci-après.<br>
<ul>
<li>Visionner la facture: $invoiceLink</li>
</ul>
Nous vous remercions de régler le montant ouvert dans les prochains jours. N'hésitez pas à nous contacter en cas de questions à ce sujet.<br>
Il est possible que votre paiement se soit croisé avec ce rappel. Dans ce cas, veuillez ignorer ce message.<br>
<br>
Je reste à votre disposition pour toutes questions ou remarques.<br>
<br>
Meilleures salutations<br>
Michael Ravedoni, Administration<br>
Course de Noël<br>
MAIL;

        return response($body, 200, [
            'Content-Type' => 'message/rfc822',
        ]);
    }

    public function generateQrBill(Client $client, Invoice $invoice)
    {
        $qrBill = QrBill::create();

        $qrBill->setCreditor(
            StructuredAddress::createWithStreet(
                'CA Sion - Course de Noël',
                'Case postale',
                '4057',
                '1950',
                'Sion',
                'CH'
            )
        );

        $qrBill->setCreditorInformation(
            CreditorInformation::create(
                'CH473000526565424140D' // This is a special QR-IBAN. Classic IBANs will not be valid here.
            )
        );

        $qrBill->setUltimateDebtor(
            StructuredAddress::createWithStreet(
                $client->invoicing_name ?? $client->name,
                $client->invoicing_address ?? $client->address,
                null,
                $client->invoicing_postal_code ?? $client->postal_code,
                $client->invoicing_locality ?? $client->locality,
                'CH'
            )
        );

        $qrBill->setPaymentAmountInformation(
            PaymentAmountInformation::create(
                'CHF',
                $invoice->total,
            )
        );

        $qrBill->setPaymentReference(
            PaymentReference::create(
                PaymentReference::TYPE_QR,
                $invoice->qr_reference
            )
        );

        $qrBill->setAdditionalInformation(
            AdditionalInformation::create(
                $invoice->title
            )
        );

        return $qrBill;
    }
}
