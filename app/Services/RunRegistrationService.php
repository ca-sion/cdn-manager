<?php

namespace App\Services;

use Exception;
use App\Models\Run;
use App\Models\Client;
use App\Models\Invoice;
use App\Helpers\AppHelper;
use App\Models\RunRegistration;
use App\Enums\InvoiceStatusEnum;
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
     * Facture les inscriptions d'un client qui ne sont pas encore facturées.
     *
     * - une facture « Brouillon » existante est recalculée (elle reçoit toutes les inscriptions non facturées ailleurs)
     * - une facture Prête / Envoyée / Payée n'est JAMAIS modifiée : les inscriptions ajoutées depuis vont sur une nouvelle facture
     * - une facture annulée ne compte pas ; les factures de prestations ne sont jamais touchées
     */
    public function createInvoiceForClient(int $clientId): Invoice
    {
        $registrations = RunRegistration::where('client_id', $clientId)->get();

        if ($registrations->isEmpty()) {
            throw new Exception('Aucune inscription trouvée pour ce client.');
        }

        $allElements = RunRegistrationElement::whereIn('run_registration_id', $registrations->pluck('id'))->get();

        if ($allElements->isEmpty()) {
            throw new Exception('Impossible de générer une facture : aucune inscription trouvée pour ce client.');
        }

        $client = $registrations->first()->client ?? Client::find($clientId);
        $quota = $client ? (int) $client->cdn_vouchers_quota : 0;

        // Auto-application du quota d'inscriptions incluses selon le contrat de partenariat
        if ($quota > 0) {
            $currentFreeCount = $allElements->where('has_free_registration_fee', true)->count();
            if ($currentFreeCount < $quota) {
                $toUpdate = $allElements->where('has_free_registration_fee', false)->take($quota - $currentFreeCount);
                foreach ($toUpdate as $el) {
                    $el->update(['has_free_registration_fee' => true]);
                }
                $allElements = RunRegistrationElement::whereIn('run_registration_id', $registrations->pluck('id'))->get();
            }
        }

        // Factures « courses » du client pour l'édition courante
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
        $runInvoices = Invoice::where('client_id', $clientId)
            ->where('edition_id', $editionId)
            ->active()
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->isRunInvoice());

        $draft = $runInvoices->first(fn (Invoice $invoice) => $invoice->isDraft());
        $lockedInvoices = $runInvoices->reject(fn (Invoice $invoice) => $invoice->isDraft());

        // Ancien format (sans identifiants d'inscriptions) déjà figé : on ne peut pas savoir ce qu'il contient.
        if ($lockedInvoices->contains(fn (Invoice $invoice) => $invoice->runRegistrationElementIds() === [])) {
            throw new Exception('Une facture courses existante (créée avant le suivi des inscriptions) est déjà prête/envoyée/payée : ajoutez les nouvelles inscriptions à la main pour éviter un doublon.');
        }

        $alreadyInvoiced = $lockedInvoices->flatMap(fn (Invoice $invoice) => $invoice->runRegistrationElementIds())->all();
        $elements = $allElements->reject(fn ($el) => in_array($el->id, $alreadyInvoiced, true))->values();

        if ($elements->isEmpty()) {
            throw new Exception('Toutes les inscriptions de ce client sont déjà facturées.');
        }

        $positions = $this->buildPositions($elements);
        $registrationIds = $elements->pluck('run_registration_id')->unique()->values();
        $dossierNumbers = $registrationIds->implode(', #');
        $clientName = $client ? $client->name : 'Client #'.$clientId;
        $invoiceTitle = 'Facture Inscriptions Courses - '.$clientName.' (Dossiers #'.$dossierNumbers.')';

        if ($draft) {
            $draft->positions = $positions->toArray();
            $draft->title = $invoiceTitle;
            $draft->note = 'Facture Inscriptions Courses regroupant les dossiers #'.$dossierNumbers;
            $draft->save();
            $invoice = $draft;
        } else {
            $invoiceNumber = InvoiceService::generateInvoiceNumber();

            $invoice = new Invoice;
            $invoice->edition_id = $editionId;
            $invoice->client_id = $clientId;
            $invoice->status = InvoiceStatusEnum::Ready;
            $invoice->title = $invoiceTitle;
            $invoice->number = $invoiceNumber;
            $invoice->date = now();
            $invoice->due_date = now()->addDays(30);
            $invoice->positions = $positions->toArray();
            $invoice->note = 'Facture Inscriptions Courses regroupant les dossiers #'.$dossierNumbers;
            $invoice->meta = ['source' => 'run_registrations'];
            $invoice->currency = 'CHF';
            $invoice->qr_reference = QrPaymentReferenceGenerator::generate(null, $invoiceNumber);
            $invoice->save();
        }

        // Lie les inscriptions facturées à cette facture
        RunRegistration::whereIn('id', $registrationIds)->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Lignes de facture : participants payants regroupés par course + ligne des inscriptions incluses.
     * Chaque ligne mémorise les inscriptions facturées (run_registration_element_ids).
     */
    protected function buildPositions($elements)
    {
        $paidElements = $elements->where('has_free_registration_fee', false);
        $freeElements = $elements->where('has_free_registration_fee', true);
        $positions = collect();

        $groupedPaid = $paidElements->groupBy(fn ($item) => $item->run_id ?: ($item->run_name ?: 'Autre'));

        foreach ($groupedPaid as $items) {
            $first = $items->first();
            $run = $first->run_id ? Run::find($first->run_id) : null;
            $product = $run?->provision?->product;
            $qty = $items->count();

            $positions->push([
                'name'                         => ($product?->name ?? ($run?->name ?? ($first->run_name ?? 'Inscription Course'))).' ('.$qty.' participant'.($qty > 1 ? 's' : '').')',
                'quantity'                     => $qty,
                'unit'                         => 'pce',
                'cost'                         => (float) ($product?->price?->amount ?? ($run?->cost ?? 0)),
                'tax_rate'                     => (float) ($product?->tax_rate ?? 8.1),
                'discount'                     => 0,
                'include_vat'                  => $product?->include_vat ?? true,
                'run_registration_element_ids' => $items->pluck('id')->all(),
            ]);
        }

        if ($freeElements->isNotEmpty()) {
            $freeQty = $freeElements->count();
            $positions->push([
                'name'                         => 'Inscriptions incluses selon partenariat et vouchers ('.$freeQty.' participant'.($freeQty > 1 ? 's' : '').')',
                'quantity'                     => $freeQty,
                'unit'                         => 'pce',
                'cost'                         => 0.00,
                'tax_rate'                     => 0.0,
                'discount'                     => 0,
                'include_vat'                  => true,
                'run_registration_element_ids' => $freeElements->pluck('id')->all(),
            ]);
        }

        return $positions;
    }
}
