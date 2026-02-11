<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Helpers\AppHelper;
use Sprain\SwissQrBill\QrBill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Sprain\SwissQrBill\QrCode\QrCode;
use Sprain\SwissQrBill\PaymentPart\Output\DisplayOptions;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;

class InvoiceService
{
    public static function generatePdf(Invoice $invoice)
    {
        // qr
        if ($invoice->is_pro_forma) {
            $qrBillOutput = null;
        } else {
            $displayOptions = new DisplayOptions;
            $displayOptions
                ->setPrintable(false)
                ->setDisplayTextDownArrows(false)
                ->setDisplayScissors(false)
                ->setPositionScissorsAtBottom(false);
            $qrBill = self::generateQrBill($invoice->client, $invoice);
            $qrBillHtmlOutput = new HtmlOutput($qrBill, 'fr');
            $qrBillOutput = $qrBillHtmlOutput
                ->setDisplayOptions($displayOptions)
                ->setQrCodeImageFormat(QrCode::FILE_FORMAT_PNG)
                ->getPaymentPart();
        }

        // pdf
        $view = View::make('pdf.invoice', ['invoice' => $invoice, 'qrBillOutput' => $qrBillOutput]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption(['defaultFont' => 'sans-serif']);
    }

    public static function generateQrBill(Client $client, Invoice $invoice)
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

    public static function generateInvoiceByClient(int $clientId)
    {
        $client = Client::find($clientId);

        if (! $client) {
            abort(404);
        }

        $invoiceNumber = self::generateInvoiceNumber();

        $provisionElementsWithProduct = $client->currentProvisionElements->where('has_product', true);
        $positions = $provisionElementsWithProduct->map(function ($item) {
            return [
                'name'        => $item->provision->name,
                'quantity'    => $item->quantity,
                'unit'        => $item->unit,
                'cost'        => $item->cost,
                'tax_rate'    => $item->tax_rate,
                'discount'    => $item->discount,
                'include_vat' => $item->include_vat,
            ];
        });

        $title = self::generateInvoicetitle();

        $referenceNumber = QrPaymentReferenceGenerator::generate(
            null,
            $invoiceNumber,
        );

        $invoice = new Invoice;
        $invoice->edition_id = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
        $invoice->client_id = $clientId;
        $invoice->status = 'ready';
        $invoice->title = $title;
        $invoice->number = $invoiceNumber;
        $invoice->date = now();
        $invoice->due_date = now()->addDays(30);
        $invoice->positions = $positions;
        $invoice->note = 'Généré automatiquement';
        $invoice->currency = 'CHF';
        $invoice->qr_reference = $referenceNumber;
        $invoice->save();

        return $invoice;
    }

    public static function generateInvoiceNumber()
    {
        $lastInvoice = Invoice::latest()->first();
        $lastInvoiceId = $lastInvoice ? $lastInvoice->id : 0;
        $edition = Edition::find(AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id'));
        $editionYear = $edition->year;

        $invoiceNumber = str_pad($lastInvoiceId + 1, 3, '0', STR_PAD_LEFT);

        return $editionYear.$invoiceNumber;
    }

    public static function generateInvoicetitle()
    {
        return 'Facture '.self::generateInvoiceNumber();
    }
}
