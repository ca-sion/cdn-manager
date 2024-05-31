<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class InvoiceService
{
    public static function generateInvoiceByClient(int $clientId)
    {
        $client = Client::find($clientId);

        if (! $client) {
            abort(404);
        }

        $lastInvoice = Invoice::latest()->first();
        $lastInvoiceId = $lastInvoice ? $lastInvoice->id : 0;

        $invoiceNumber = self::generateInvoiceNumber();

        $provisionElementsWithProduct = $client->provisionElements->where('has_product', true);
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
        $invoice->edition_id = session('edition_id');
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
        $edition = Edition::find(config('cdn.default_edition_id'));
        $editionYear = $edition->year;

        $invoiceNumber = str_pad($lastInvoiceId + 1, 3, '0', STR_PAD_LEFT);

        return $editionYear.$invoiceNumber;
    }

    public static function generateInvoicetitle()
    {
        return 'Facture '.self::generateInvoiceNumber();
    }
}
