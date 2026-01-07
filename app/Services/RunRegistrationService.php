<?php

namespace App\Services;

use App\Models\Run;
use App\Models\RunRegistration;

use App\Models\Invoice;
use App\Helpers\AppHelper;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class RunRegistrationService
{
    /**
     * Calcule le montant total d'une inscription en sommant le coût des courses liées.
     * Les éléments marqués avec 'has_free_registration_fee' ne sont pas comptabilisés.
     */
    public function calculateTotal(RunRegistration $registration): float
    {
        return $registration->runRegistrationElements()
            ->where('has_free_registration_fee', false)
            ->get()
            ->sum(function ($element) {
                return $element->run?->cost ?? 0;
            });
    }

    /**
     * Vérifie si l'inscription à une course est toujours ouverte.
     */
    public function isRegistrationOpen(Run $run): bool
    {
        if (!$run->registrations_deadline) {
            return true;
        }

        return now()->lessThanOrEqualTo($run->registrations_deadline);
    }

    /**
     * Génère une facture à partir d'une inscription.
     */
    public function createInvoice(RunRegistration $registration): Invoice
    {
        $clientId = $registration->client_id;

        if (! $clientId) {
            throw new \Exception('L\'inscription doit être liée à un client pour générer une facture.');
        }

        $invoiceNumber = InvoiceService::generateInvoiceNumber();

        $elements = $registration->runRegistrationElements()->where('has_free_registration_fee', false)->get();

        $positions = $elements->map(function ($item) {
            return [
                'name'        => $item->run?->name ?? $item->run_name,
                'quantity'    => 1,
                'unit'        => 'pce',
                'cost'        => $item->run?->cost ?? 0,
                'tax_rate'    => 8.1,
                'discount'    => 0,
                'include_vat' => true,
            ];
        });

        $invoice = new Invoice;
        $invoice->edition_id = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
        $invoice->client_id = $clientId;
        $invoice->status = 'ready';
        $invoice->title = 'Facture Inscription Course - '.($registration->company_name ?? $registration->school_name ?? $registration->contact_last_name);
        $invoice->number = $invoiceNumber;
        $invoice->date = now();
        $invoice->due_date = now()->addDays(30);
        $invoice->positions = $positions->toArray();
        $invoice->note = 'Généré automatiquement depuis inscription';
        $invoice->currency = 'CHF';
        $invoice->qr_reference = QrPaymentReferenceGenerator::generate(null, $invoiceNumber);
        $invoice->save();

        return $invoice;
    }
}
