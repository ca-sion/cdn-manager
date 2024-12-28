<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Sprain\SwissQrBill\QrBill;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Facades\View;
use Sprain\SwissQrBill\QrCode\QrCode;
use App\Notifications\ClientSendInvoice;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $qrBill = $this->generateQrBill($invoice->client, $invoice);
        $qrBillHtmlOutput = new HtmlOutput($qrBill, 'fr');
        $qrBillOutput = $qrBillHtmlOutput
            ->setPrintable(false)
            ->setQrCodeImageFormat(QrCode::FILE_FORMAT_PNG)
            ->getPaymentPart();

        $view = View::make('pdf.invoice', ['invoice' => $invoice, 'qrBillOutput' => $qrBillOutput]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption(['defaultFont' => 'sans-serif'])
            ->stream($invoice->number.'.pdf');

        return $pdf;
    }

    public function eml(Invoice $invoice)
    {
        $dueDate = Carbon::parse($invoice->due_date)->locale('fr_CH')->isoFormat('L');
        $invoiceLink = quoted_printable_encode($invoice->link);
        $recipient = $invoice->client?->invoicingContactEmail;
        $notification = (new ClientSendInvoice($invoice))->toMail($invoice->client);
        $notificationHtmlString = $notification->render()->__toString();

        $invoice->status = InvoiceStatusEnum::Sent;
        $invoice->save();

        $body = <<<MAIL
From: Course de =?utf-8?Q?No=C3=ABl?= <info@coursedenoel.ch>
To: $recipient
Subject: Course de Noël 2024 - Facture (F$invoice->number)
MIME-Version: 1.0
Date: Sat, 28 Dec 2024 20:17:47 +0000
Message-ID: <23307f2fd117e41de1a18a7a135e95f1@coursedenoel.ch>
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Bonjour,

Nous vous adressons la pr=C3=A9sente facture pour le versem=
ent du montant convenu. Pour la visionner, cliquer sur le lien ci-apr=
=C3=A8s.

- Visionner la facture: $invoiceLink

Vous pouvez la payer d'ici au $dueDate en utilisant le bulletin de =
versement (QR-facture) attach=C3=A9.

Nous vous remercions chaleureusem=
ent pour votre g=C3=A9n=C3=A9reux soutien.

Je reste =C3=A0 votre dispo=
sition pour toutes questions ou remarques.

Meilleures salutations

Micha=
el Ravedoni, Administration
Course de Noël
MAIL;

        return response($body, 200, [
            'Content-Type' => 'message/rfc822',
        ]);
    }

    public function emlRelaunch(Invoice $invoice)
    {
        $date = Carbon::parse($invoice->date)->locale('fr_CH')->isoFormat('L');
        $invoiceLink = quoted_printable_encode($invoice->link);
        $recipient = $invoice->client?->invoicingContactEmail;

        $invoice->status = InvoiceStatusEnum::Relaunched;
        $invoice->save();

        $body = <<<MAIL
From: Course de =?utf-8?Q?No=C3=ABl?= <info@coursedenoel.ch>
To: $recipient
Subject: Course de Noël 2024 - Facture (F$invoice->number) : rappel
MIME-Version: 1.0
Date: Sat, 28 Dec 2024 20:17:47 +0000
Message-ID: <23307f2fd117e41de1a18a7a135e95f1@coursedenoel.ch>
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Bonjour,

Sauf erreur de notre part, le paiement de la facture F$invoice->number du $date ne nous est pas parvenu.

Pour visionner votre facture, cliquer sur le lien ci-après : $invoiceLink

Nous vous remercions de régler le montant ouvert dans les prochains jours. N'hésitez pas à nous contacter en cas de questions à ce sujet.
Il est possible que votre paiement se soit croisé avec ce rappel. Dans ce cas, veuillez ignorer ce message.

Je reste à votre disposition pour toutes questions ou remarques.
Meilleures salutations

Michael Ravedoni, Administration
Course de Noël
MAIL;

        return response($body, 200, [
            'Content-Type' => 'message/rfc822',
        ]);
    }

    public function generateQrBill(Client $client, Invoice $invoice)
    {
        $qrBill = QrBill::create();

        $qrBill->setCreditor(
            CombinedAddress::create(
                'CA Sion - Course de Noël',
                'Case postale 4057',
                '1950 Sion',
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
