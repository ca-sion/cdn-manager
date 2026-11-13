<?php

namespace App\Services;

use Exception;
use App\Models\Run;
use App\Models\Client;
use App\Models\Invoice;
use App\Helpers\AppHelper;
use App\Models\RunRegistration;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\RunRegistrationElement;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class RunRegistrationService
{
    /** @var array<string, array<int, mixed>> données chargées en une fois pour une liste de clients */
    protected array $primed = [];

    /** @var array<int, ?Run> */
    protected array $runCache = [];

    /**
     * Charge en quelques requêtes les inscriptions, participants, factures et clients de plusieurs clients
     * (au lieu de ~6 requêtes par client). À utiliser avant de calculer des aperçus pour une longue liste.
     *
     * @param  array<int, int>  $clientIds
     */
    public function prime(array $clientIds): void
    {
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

        $registrations = RunRegistration::whereIn('client_id', $clientIds)->get();
        $elements = RunRegistrationElement::with('runRegistration')
            ->whereIn('run_registration_id', $registrations->pluck('id'))
            ->get();
        $invoices = Invoice::whereIn('client_id', $clientIds)
            ->where('edition_id', $editionId)
            ->active()
            ->where(fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma'))
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->isRunInvoice())
            ->groupBy('client_id');
        $clients = Client::whereIn('id', $clientIds)->get()->keyBy('id');

        $registrationsByClient = $registrations->groupBy('client_id');
        foreach ($clientIds as $id) {
            $ids = ($registrationsByClient[$id] ?? collect())->pluck('id');
            $this->primed[$id] = [
                'registrations' => ($registrationsByClient[$id] ?? collect())->values(),
                'elements'      => $elements->whereIn('run_registration_id', $ids)->values(),
                'invoices'      => ($invoices[$id] ?? collect())->values(),
                'client'        => $clients[$id] ?? null,
            ];
        }
    }

    public function flush(): void
    {
        $this->primed = [];
    }

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
     * Simulation de la facturation des inscriptions d'un client : RIEN n'est écrit.
     * Sert à la fois à l'aperçu (actions en masse) et à la génération réelle.
     *
     * Règles :
     * - les inscriptions Élite ne sont jamais facturées automatiquement
     * - une facture « Brouillon » existante est recalculée ; Prête / Envoyée / Payée n'est jamais modifiée
     * - une facture annulée ou une proforma ne compte pas ; les factures de prestations ne sont jamais touchées
     * - le quota « inscriptions incluses » ne s'applique qu'aux inscriptions pas encore facturées
     *
     * @return array<string, mixed>
     */
    public function plan(int $clientId): array
    {
        $isElite = fn ($registration) => ($registration->run_registration_type?->value ?? $registration->run_registration_type) === 'elite';

        $primed = $this->primed[$clientId] ?? null;

        $all = $primed['registrations'] ?? RunRegistration::where('client_id', $clientId)->get();
        $registrations = $all->reject($isElite)->values();
        $eliteIds = $all->filter($isElite)->pluck('id');
        $eliteExcluded = $primed
            ? $primed['elements']->whereIn('run_registration_id', $eliteIds)->count()
            : RunRegistrationElement::whereIn('run_registration_id', $eliteIds)->count();

        $allElements = $primed
            ? $primed['elements']->whereIn('run_registration_id', $registrations->pluck('id'))->values()
            : RunRegistrationElement::with('runRegistration')->whereIn('run_registration_id', $registrations->pluck('id'))->get();

        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
        $runInvoices = $primed['invoices'] ?? Invoice::where('client_id', $clientId)
            ->where('edition_id', $editionId)
            ->active()
            ->where(fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma'))
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->isRunInvoice())
            ->values();

        $draft = $runInvoices->first(fn (Invoice $invoice) => $invoice->isDraft());
        $locked = $runInvoices->reject(fn (Invoice $invoice) => $invoice->isDraft());

        $invoicedIds = $this->invoicedElementIds($locked, $allElements);
        $elements = $allElements->reject(fn ($el) => in_array($el->id, $invoicedIds, true))->values();

        // Quota d'inscriptions incluses (contrat de partenariat), uniquement sur ce qui reste à facturer
        $client = $primed ? $primed['client'] : Client::find($clientId);
        $quota = $client ? (int) $client->cdn_vouchers_quota : 0;
        $quotaIds = [];
        if ($quota > 0) {
            $alreadyFree = $allElements->where('has_free_registration_fee', true)->count();
            if ($alreadyFree < $quota) {
                $quotaIds = $elements->where('has_free_registration_fee', false)->take($quota - $alreadyFree)->pluck('id')->all();
            }
        }

        $simulated = $elements->map(function ($el) use ($quotaIds) {
            $copy = clone $el;
            if (in_array($el->id, $quotaIds, true)) {
                $copy->has_free_registration_fee = true;
            }

            return $copy;
        });

        $positions = $this->buildPositions($simulated);
        $total = (float) (new Invoice(['positions' => $positions->toArray()]))->total;

        $warnings = [];
        $zeroLines = $positions->filter(fn ($p) => ($p['cost'] ?? 0) == 0 && ! str_starts_with($p['name'], 'Inscriptions incluses'));
        if ($zeroLines->isNotEmpty()) {
            $warnings[] = 'ligne(s) à 0 CHF (course ou produit introuvable) : '.$zeroLines->pluck('name')->implode(', ');
        }
        if ($eliteExcluded > 0) {
            $warnings[] = $eliteExcluded.' inscription(s) Élite non facturée(s)';
        }

        return [
            'client_id'        => $clientId,
            'client'           => $client,
            'total_elements'   => $allElements->count(),
            'invoiced'         => count($invoicedIds),
            'elements'         => $elements,
            'positions'        => $positions,
            'amount'           => $total,
            'quota_ids'        => $quotaIds,
            'registration_ids' => $elements->pluck('run_registration_id')->unique()->values(),
            'draft'            => $draft,
            'invoices'         => $runInvoices,
            'elite_excluded'   => $eliteExcluded,
            'warnings'         => $warnings,
            'registrations'    => $registrations,
        ];
    }

    /**
     * Facture les inscriptions d'un client qui ne sont pas encore facturées (voir plan() pour les règles).
     * Tout est exécuté dans une transaction : quota, facture et liaison des dossiers réussissent ensemble ou pas du tout.
     */
    public function createInvoiceForClient(int $clientId): Invoice
    {
        $plan = $this->plan($clientId);

        if ($plan['registrations']->isEmpty()) {
            throw new Exception($plan['elite_excluded'] > 0
                ? 'Seules des inscriptions Élite : elles ne sont pas facturées automatiquement.'
                : 'Aucune inscription trouvée pour ce client.');
        }

        if ($plan['total_elements'] === 0) {
            throw new Exception('Impossible de générer une facture : aucune inscription trouvée pour ce client.');
        }

        if ($plan['elements']->isEmpty()) {
            throw new Exception('Toutes les inscriptions de ce client sont déjà facturées.');
        }

        return DB::transaction(function () use ($plan, $clientId) {
            // Applique réellement le quota calculé
            if ($plan['quota_ids'] !== []) {
                RunRegistrationElement::whereIn('id', $plan['quota_ids'])->update(['has_free_registration_fee' => true]);
            }

            $note = 'Facture d\'inscriptions';

            if ($draft = $plan['draft']) {
                $draft->positions = $plan['positions']->toArray();
                $draft->title = 'Facture d\'inscriptions '.$draft->number;
                $draft->note = $note;
                $draft->save();
                $invoice = $draft;
            } else {
                $invoice = InvoiceService::createWithNumber(function (string $invoiceNumber) use ($plan, $clientId, $note) {
                    $invoice = new Invoice;
                    $invoice->edition_id = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
                    $invoice->client_id = $clientId;
                    $invoice->status = InvoiceStatusEnum::Ready;
                    $invoice->title = 'Facture d\'inscriptions '.$invoiceNumber;
                    $invoice->number = $invoiceNumber;
                    $invoice->date = now();
                    $invoice->due_date = now()->addDays(30);
                    $invoice->positions = $plan['positions']->toArray();
                    $invoice->note = $note;
                    $invoice->meta = ['source' => 'run_registrations'];
                    $invoice->currency = 'CHF';
                    $invoice->qr_reference = QrPaymentReferenceGenerator::generate(null, $invoiceNumber);
                    $invoice->save();

                    return $invoice;
                });
            }

            RunRegistration::whereIn('id', $plan['registration_ids'])->update(['invoice_id' => $invoice->id]);
            unset($this->primed[$clientId]);

            return $invoice;
        });
    }

    /**
     * Inscriptions couvertes par des factures courses : identifiants mémorisés dans la facture, ou
     * (anciennes factures) les plus anciens inscrits des dossiers liés, autant que la somme des quantités.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, int>
     */
    protected function invoicedElementIds($invoices, $allElements): array
    {
        return $invoices->flatMap(function (Invoice $invoice) use ($allElements) {
            $tracked = $invoice->runRegistrationElementIds();
            if ($tracked !== []) {
                return $tracked;
            }

            $covered = (int) collect($invoice->positions ?? [])->sum('quantity');
            $linked = $allElements
                ->filter(fn ($el) => (int) $el->runRegistration?->invoice_id === $invoice->id)
                ->sortBy('id');

            return ($covered > 0 ? $linked->take($covered) : $linked)->pluck('id')->all();
        })->all();
    }

    /**
     * Situation de facturation des inscriptions de courses d'un client (pour expliquer l'action à l'utilisateur).
     *
     * @return array{total: int, to_invoice: int, invoiced: int, invoices: Collection<int, Invoice>, draft: ?Invoice}
     */
    public function billingOverview(int $clientId): array
    {
        $plan = $this->plan($clientId);

        return [
            'total'      => $plan['total_elements'],
            'invoiced'   => $plan['invoiced'],
            'to_invoice' => $plan['elements']->count(),
            'invoices'   => $plan['invoices'],
            'draft'      => $plan['draft'],
        ];
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
            $run = $first->run_id ? ($this->runCache[$first->run_id] ??= Run::with('provision.product')->find($first->run_id)) : null;
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
