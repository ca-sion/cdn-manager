<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Helpers\AppHelper;
use Sprain\SwissQrBill\QrBill;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use Illuminate\Support\Collection;
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
    /** @var array<int, Collection<int, Invoice>> */
    protected static array $stateCache = [];

    /** @var array<int, Collection<int, ProvisionElement>> */
    protected static array $elementsCache = [];

    public static function flushCache(): void
    {
        self::$stateCache = [];
        self::$elementsCache = [];
    }

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
                'Course de Noël et Trail des Châteaux',
                'Case postale',
                '4057',
                '1950',
                'Sion',
                'CH'
            )
        );

        $qrBill->setCreditorInformation(
            CreditorInformation::create(
                'CH043000526515567201T' // This is a special QR-IBAN. Classic IBANs will not be valid here.
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

    /**
     * Invoices of the client for the current edition that still count (not cancelled), excluding run invoices.
     *
     * @return Collection<int, Invoice>
     */
    public static function provisionInvoices(int $clientId): Collection
    {
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

        return Invoice::where('client_id', $clientId)
            ->where('edition_id', $editionId)
            ->active()
            ->where(fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma'))
            ->get()
            ->reject(fn (Invoice $invoice) => $invoice->isRunInvoice())
            ->values();
    }

    /**
     * Ids of the provision elements covered by an invoice.
     * - invoices generated since tracking exists carry the ids in their positions
     * - older invoices carry none: they cover the provisions that existed when the invoice was created
     *
     * @param  Collection<int, ProvisionElement>  $elements  candidates (client's invoiceable provisions)
     * @return array<int, int>
     */
    public static function invoicedElementIds(Invoice $invoice, $elements): array
    {
        $tracked = $invoice->provisionElementIds();
        if ($tracked !== []) {
            return $tracked;
        }

        return $elements
            ->filter(fn (ProvisionElement $element) => $element->created_at <= $invoice->created_at)
            ->pluck('id')
            ->all();
    }

    /**
     * The client's invoiceable provisions for the current edition.
     *
     * @return Collection<int, ProvisionElement>
     */
    public static function invoiceableElements(int $clientId)
    {
        return Client::findOrFail($clientId)
            ->currentProvisionElements()
            ->with('provision.category')
            ->get()
            ->filter(fn (ProvisionElement $element) => $element->isInvoiceable())
            ->values();
    }

    /**
     * Provision elements of the client (current edition, with a product) not yet on any active invoice.
     *
     * @return Collection<int, ProvisionElement>
     */
    public static function uninvoicedProvisionElements(int $clientId)
    {
        $candidates = self::invoiceableElements($clientId);

        $invoicedIds = self::provisionInvoices($clientId)
            ->flatMap(fn (Invoice $invoice) => self::invoicedElementIds($invoice, $candidates))
            ->all();

        return $candidates
            ->reject(fn (ProvisionElement $element) => in_array($element->id, $invoicedIds, true))
            ->values();
    }

    /**
     * Billing overview of a client's provisions (current edition), used to explain the situation in the UI.
     *
     * @return array{
     *     to_invoice: Collection<int, ProvisionElement>,
     *     invoiced: Collection<int, array{element: ProvisionElement, invoice: Invoice, recognised: bool}>,
     *     without_product: int,
     *     legacy_invoices: Collection<int, Invoice>
     * }
     */
    public static function billingOverview(int $clientId): array
    {
        $all = Client::findOrFail($clientId)->currentProvisionElements()->with('provision.category')->get();
        $candidates = self::invoiceableElements($clientId);

        $invoiced = collect();
        $taken = [];
        foreach (self::provisionInvoices($clientId) as $invoice) {
            $tracked = $invoice->provisionElementIds() !== [];
            foreach (self::invoicedElementIds($invoice, $candidates) as $id) {
                if ($element = $candidates->firstWhere('id', $id)) {
                    $invoiced->push(['element' => $element, 'invoice' => $invoice, 'recognised' => ! $tracked]);
                    $taken[] = $id;
                }
            }
        }

        return [
            'to_invoice'      => $candidates->reject(fn ($e) => in_array($e->id, $taken, true))->values(),
            'invoiced'        => $invoiced,
            'without_product' => $all->count() - $candidates->count(),
            'legacy_invoices' => self::legacyProvisionInvoices($clientId),
        ];
    }

    /**
     * Active provision invoices created before provisions were tracked (positions without ids).
     *
     * @return Collection<int, Invoice>
     */
    public static function legacyProvisionInvoices(int $clientId)
    {
        return self::provisionInvoices($clientId)
            ->filter(fn (Invoice $invoice) => $invoice->provisionElementIds() === [] && ! empty($invoice->positions));
    }

    /**
     * Draft invoice of the client that can still receive new provisions.
     */
    public static function openDraftInvoice(int $clientId): ?Invoice
    {
        return self::provisionInvoices($clientId)
            ->first(fn (Invoice $invoice) => $invoice->isDraft());
    }

    /**
     * Generate an invoice from the client's not yet invoiced provisions.
     * - a draft invoice is completed; otherwise a new « Ready » invoice is created
     * - sent / paid / ready invoices are never modified
     *
     * @param  array<int, int>|null  $elementIds  restrict to these provision elements (null = all not yet invoiced)
     */
    public static function generateInvoiceByClient(int $clientId, ?array $elementIds = null): Invoice
    {
        $client = Client::find($clientId);

        if (! $client) {
            abort(404);
        }

        $elements = self::uninvoicedProvisionElements($clientId);

        if ($elementIds !== null) {
            $elements = $elements->filter(fn (ProvisionElement $e) => in_array($e->id, array_map('intval', $elementIds), true))->values();
        }

        if ($elements->isEmpty()) {
            throw new \RuntimeException('Aucune prestation à facturer : tout est déjà facturé (ou rien n\'est sélectionné).');
        }

        $positions = $elements->map(fn (ProvisionElement $e) => $e->invoicePosition())->values()->all();

        $draft = self::openDraftInvoice($clientId);
        if ($draft) {
            $draft->positions = array_merge($draft->positions ?? [], $positions);
            $draft->save();

            return $draft;
        }

        $invoiceNumber = self::generateInvoiceNumber();

        $invoice = new Invoice;
        $invoice->edition_id = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
        $invoice->client_id = $clientId;
        $invoice->status = InvoiceStatusEnum::Ready;
        $invoice->title = self::generateInvoicetitle();
        $invoice->number = $invoiceNumber;
        $invoice->date = now();
        $invoice->due_date = now()->addDays(30);
        $invoice->positions = $positions;
        $invoice->note = 'Généré automatiquement';
        $invoice->currency = 'CHF';
        $invoice->qr_reference = QrPaymentReferenceGenerator::generate(null, $invoiceNumber);
        $invoice->save();

        return $invoice;
    }

    /**
     * @return Collection<int, ProvisionElement>
     */
    protected static function currentElementsOf(int $clientId)
    {
        return self::$elementsCache[$clientId] ??= self::invoiceableElements($clientId);
    }

    /**
     * State of a provision element regarding invoicing.
     * Returns [state, invoice|null] where state is one of: none, to_invoice, invoiced, modified.
     *
     * @return array{0: string, 1: ?Invoice}
     */
    public static function provisionElementInvoiceState(ProvisionElement $element): array
    {
        if (! $element->isInvoiceable() || $element->recipient_type !== Client::class) {
            return ['none', null];
        }

        $clientId = (int) $element->recipient_id;
        self::$stateCache[$clientId] ??= self::provisionInvoices($clientId);

        foreach (self::$stateCache[$clientId] as $invoice) {
            $tracked = $invoice->provisionElementIds() !== [];
            $ids = $tracked ? $invoice->provisionElementIds() : self::invoicedElementIds($invoice, self::currentElementsOf($clientId));

            if (in_array($element->id, $ids, true)) {
                // Only tracked invoices hold an exact snapshot to compare with
                $snapshot = $tracked ? collect($invoice->positions)->firstWhere('provision_element_id', $element->id) : null;
                $current = $element->invoicePosition();
                foreach ($snapshot ? ['quantity', 'cost', 'tax_rate', 'discount', 'include_vat'] : [] as $key) {
                    if ((string) ($snapshot[$key] ?? '') !== (string) ($current[$key] ?? '')) {
                        return ['modified', $invoice];
                    }
                }

                return ['invoiced', $invoice];
            }
        }

        return ['to_invoice', null];
    }

    /**
     * Keep a draft invoice in sync with a provision element that changed or was removed.
     * Non-draft invoices are never touched (the element is flagged as « modified » instead).
     */
    public static function syncDraftInvoiceWith(ProvisionElement $element, bool $removed = false): void
    {
        if ($element->recipient_type !== Client::class || ! $element->recipient_id) {
            return;
        }

        $draft = self::provisionInvoices((int) $element->recipient_id)
            ->first(fn (Invoice $invoice) => $invoice->isDraft() && in_array($element->id, $invoice->provisionElementIds(), true));

        if (! $draft) {
            return;
        }

        $positions = collect($draft->positions)->map(function ($position) use ($element, $removed) {
            if ((int) ($position['provision_element_id'] ?? 0) !== $element->id) {
                return $position;
            }

            return $removed || ! $element->isInvoiceable() ? null : $element->invoicePosition();
        })->filter()->values()->all();

        $draft->positions = $positions;
        $draft->save();
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
