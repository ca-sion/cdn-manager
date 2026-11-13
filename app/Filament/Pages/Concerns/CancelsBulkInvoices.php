<?php

namespace App\Filament\Pages\Concerns;

use Livewire\Attributes\On;
use App\Services\InvoiceService;
use Filament\Notifications\Notification;

/**
 * À utiliser sur les pages de liste qui proposent une génération de factures en masse :
 * le bouton « Annuler ces factures » du rapport déclenche cet événement.
 */
trait CancelsBulkInvoices
{
    /**
     * @param  array<int, int>  $invoiceIds
     * @param  array<int, array{before: array, after: array}>  $engagements  suivi des clients avant / après le bulk
     */
    #[On('undo-bulk-invoices')]
    public function undoBulkInvoices(array $invoiceIds, array $engagements = []): void
    {
        $result = InvoiceService::cancelGeneratedInvoices($invoiceIds, $engagements);

        Notification::make()
            ->title($result['cancelled'].' facture(s) annulée(s)')
            ->body($result['skipped'] > 0
                ? $result['skipped'].' facture(s) ont déjà changé de statut (envoyée, payée…) et n\'ont pas été touchées.'
                : 'Les prestations / inscriptions concernées peuvent de nouveau être facturées et le suivi des clients est remis comme avant.')
            ->{$result['skipped'] > 0 ? 'warning' : 'success'}()
            ->send();
    }
}
