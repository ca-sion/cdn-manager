<?php

namespace App\Services;

use Exception;
use App\Models\Run;
use App\Models\Invoice;
use App\Helpers\AppHelper;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class RunRegistrationService
{
    /**
     * Calcule le montant total d'une inscription en sommant le coût des courses liées.
     * Les éléments marqués avec 'has_free_registration_fee' ne sont pas comptabilisés.
     */
    public function calculateTotal(RunRegistration $registration): float
    {
        return $registration->calculateEstimatedTotal();
    }

    /**
     * Vérifie si l'inscription à une course est toujours ouverte.
     */
    public function isRegistrationOpen(Run $run): bool
    {
        if (! $run->registrations_deadline) {
            return true;
        }

        return now()->lessThanOrEqualTo($run->registrations_deadline);
    }

    /**
     * Génère ou met à jour la facture consolidée pour un CLIENT (regroupant toutes ses inscriptions).
     * Empêche la création de factures pour des clients sans participants et évite les doublons.
     */
    public function createInvoice(RunRegistration $registration): Invoice
    {
        $clientId = $registration->client_id;

        if (! $clientId) {
            throw new Exception('L\'inscription doit être liée à un client pour générer une facture.');
        }

        return $this->createInvoiceForClient($clientId);
    }

    /**
     * Crée ou réactualise la facture unique consolidée pour l'ensemble des inscriptions d'un même client.
     */
    public function createInvoiceForClient(int $clientId): Invoice
    {
        // Récupère toutes les inscriptions du client
        $registrations = RunRegistration::where('client_id', $clientId)->get();

        if ($registrations->isEmpty()) {
            throw new Exception('Aucune inscription trouvée pour ce client.');
        }

        $registrationIds = $registrations->pluck('id');

        // Tous les éléments (payants et vouchers) de l'ensemble des dossiers de ce client
        $allElements = RunRegistrationElement::whereIn('run_registration_id', $registrationIds)->get();

        if ($allElements->isEmpty()) {
            throw new Exception('Impossible de générer une facture : aucune inscription trouvée pour ce client.');
        }

        $paidElements = $allElements->where('has_free_registration_fee', false);
        $freeElements = $allElements->where('has_free_registration_fee', true);

        if ($paidElements->isEmpty() && $freeElements->isEmpty()) {
            throw new Exception('Impossible de générer une facture : aucun participant trouvé pour ce client.');
        }

        $positions = collect();

        // Lignes des participants payants regroupées par course
        if ($paidElements->isNotEmpty()) {
            $groupedPaid = $paidElements->groupBy(fn ($item) => $item->run_id ?: ($item->run_name ?: 'Autre'));

            foreach ($groupedPaid as $items) {
                $first = $items->first();
                $run = $first->run_id ? Run::find($first->run_id) : null;

                $provision = $run?->provision;
                $product = $provision?->product;

                $name = $product?->name ?? ($run?->name ?? ($first->run_name ?? 'Inscription Course'));
                $unitCost = (float) ($product?->price?->amount ?? ($run?->cost ?? 0));
                $taxRate = (float) ($product?->tax_rate ?? 8.1);
                $includeVat = $product?->include_vat ?? true;
                $qty = $items->count();

                $positions->push([
                    'name'        => $name . ' (' . $qty . ' participant' . ($qty > 1 ? 's' : '') . ')',
                    'quantity'    => $qty,
                    'unit'        => 'pce',
                    'cost'        => $unitCost,
                    'tax_rate'    => $taxRate,
                    'discount'    => 0,
                    'include_vat' => $includeVat,
                ]);
            }
        }

        // Ligne distincte pour les dossards offerts / Vouchers
        if ($freeElements->isNotEmpty()) {
            $freeQty = $freeElements->count();
            $positions->push([
                'name'        => 'Dossards offerts / Vouchers déduits (' . $freeQty . ' participant' . ($freeQty > 1 ? 's' : '') . ')',
                'quantity'    => $freeQty,
                'unit'        => 'pce',
                'cost'        => 0.00,
                'tax_rate'    => 0.0,
                'discount'    => 0,
                'include_vat' => true,
            ]);
        }

        $client = $registrations->first()->client;
        $clientName = $client ? $client->name : 'Client #' . $clientId;
        $dossierNumbers = $registrationIds->implode(', #');
        $invoiceTitle = 'Facture Inscriptions Courses - ' . $clientName . ' (Dossiers #' . $dossierNumbers . ')';

        // Recherche d'une facture non-payée existante pour ce client dans l'édition courante
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

        $invoice = Invoice::where('client_id', $clientId)
            ->where('edition_id', $editionId)
            ->whereNotIn('status', ['paid', 'canceled'])
            ->first();

        if ($invoice) {
            $invoice->positions = $positions->toArray();
            $invoice->title = $invoiceTitle;
            $invoice->save();
        } else {
            $invoiceNumber = InvoiceService::generateInvoiceNumber();

            $invoice = new Invoice;
            $invoice->edition_id = $editionId;
            $invoice->client_id = $clientId;
            $invoice->status = 'ready';
            $invoice->title = $invoiceTitle;
            $invoice->number = $invoiceNumber;
            $invoice->date = now();
            $invoice->due_date = now()->addDays(30);
            $invoice->positions = $positions->toArray();
            $invoice->note = 'Facture Inscriptions Courses regroupant les dossiers #' . $dossierNumbers;
            $invoice->currency = 'CHF';
            $invoice->qr_reference = QrPaymentReferenceGenerator::generate(null, $invoiceNumber);
            $invoice->save();
        }

        // Met à jour la liaison invoice_id sur toutes les inscriptions du client
        RunRegistration::whereIn('id', $registrationIds)->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }
}
