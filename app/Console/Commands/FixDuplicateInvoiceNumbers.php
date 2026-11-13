<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use App\Enums\InvoiceStatusEnum;
use App\Services\InvoiceService;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

/**
 * Détecte les numéros de facture en double et renumérote les factures NON envoyées (Brouillon / Prêt).
 * Une facture envoyée, payée, etc. n'est jamais renumérotée : elle est signalée pour correction manuelle.
 *
 * Sans --apply, la commande ne fait que simuler.
 */
class FixDuplicateInvoiceNumbers extends Command
{
    protected $signature = 'invoices:fix-duplicate-numbers {--apply : Applique réellement les changements (sinon simulation)}';

    protected $description = 'Liste les numéros de facture en double et renumérote les factures non envoyées';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $groups = Invoice::withoutGlobalScopes()
            ->whereNotNull('number')
            ->orderBy('id')
            ->get()
            ->groupBy('number')
            ->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('Aucun numéro de facture en double.');

            return self::SUCCESS;
        }

        $rows = [];
        $manual = 0;

        foreach ($groups as $number => $group) {
            // La facture qui garde le numéro : en priorité une facture déjà envoyée (non renumérotable), sinon la plus ancienne.
            $sorted = $group->sortBy(fn (Invoice $i) => [$this->renumberable($i) ? 1 : 0, $i->id])->values();
            $keep = $sorted->first();
            $rows[] = [$number, $keep->id, $keep->status?->value, $keep->type(), 'garde le numéro'];

            foreach ($sorted->slice(1) as $invoice) {
                if (! $this->renumberable($invoice)) {
                    $manual++;
                    $rows[] = [$number, $invoice->id, $invoice->status?->value, $invoice->type(), 'À CORRIGER À LA MAIN (facture déjà envoyée / payée)'];

                    continue;
                }

                if ($apply) {
                    $new = InvoiceService::createWithNumber(function (string $new) use ($invoice, $number) {
                        $invoice->update([
                            'number'       => $new,
                            'qr_reference' => QrPaymentReferenceGenerator::generate(null, $new),
                            'title'        => $this->retitle($invoice, $number, $new),
                        ]);

                        return $new;
                    });
                    $rows[] = [$number, $invoice->id, $invoice->status?->value, $invoice->type(), "renumérotée → {$new}"];
                } else {
                    $rows[] = [$number, $invoice->id, $invoice->status?->value, $invoice->type(), 'serait renumérotée'];
                }
            }
        }

        $this->table(['Numéro', 'Facture (id)', 'Statut', 'Type', 'Action'], $rows);

        if (! $apply) {
            $this->warn('Simulation : rien n\'a été modifié. Relancez avec --apply pour renuméroter.');
        }

        if ($manual > 0) {
            $this->error($manual.' facture(s) déjà envoyée(s) ou payée(s) partagent encore un numéro : à corriger à la main.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function renumberable(Invoice $invoice): bool
    {
        return in_array($invoice->status, [InvoiceStatusEnum::Draft, InvoiceStatusEnum::Ready], true);
    }

    /**
     * Le titre contient le numéro (« Facture 2025007 », « Facture d'inscriptions 2025007 ») : on le met à jour.
     */
    protected function retitle(Invoice $invoice, string $old, string $new): ?string
    {
        $raw = $invoice->getRawOriginal('title');

        return $raw !== null && str_contains($raw, $old) ? str_replace($old, $new, $raw) : $raw;
    }
}
