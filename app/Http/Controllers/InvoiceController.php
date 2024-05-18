<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Sprain\SwissQrBill\QrBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Sprain\SwissQrBill\QrCode\QrCode;
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
