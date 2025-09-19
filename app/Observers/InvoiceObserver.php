<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\ClientEngagement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $engagement = ClientEngagement::where('client_id', $invoice->client_id)
                                      ->where('edition_id', $invoice->edition_id)
                                      ->first();

        if ($engagement) {
            $engagement->stage = EngagementStageEnum::Billed;
            $engagement->status = EngagementStatusEnum::Idle;
            $engagement->save();
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $engagement = ClientEngagement::where('client_id', $invoice->client_id)
                                      ->where('edition_id', $invoice->edition_id)
                                      ->first();

        if (!$engagement || !$invoice->isDirty('status')) {
            return;
        }

        // Cas : Facture payée
        if ($invoice->status === 'paid') {
            $allInvoicesPaid = $engagement->invoices()->where('status', '<>', 'paid')->doesntExist();

            if ($allInvoicesPaid) {
                $engagement->stage = EngagementStageEnum::Paid;
                
                // Si toutes les factures sont payées, on vérifie si des provisions sont encore à faire
                $pendingProvisions = $engagement->provisionElements()->whereIn('status', [
                    'to_prepare', 'confirmed', 'ready', 'to_confirm', 'to_modify', 'action_required', 'to_relaunch'
                ])->exists();
                
                if ($pendingProvisions) {
                    $engagement->status = EngagementStatusEnum::ActionRequired;
                } else {
                    $engagement->status = null; // C'est terminé
                }
            }
        } 
        
        // Cas : Facture envoyée
        elseif ($invoice->status === 'sent') {
            if ($engagement->stage->isNot(EngagementStageEnum::Billed)) {
                $engagement->stage = EngagementStageEnum::Billed;
            }
            $engagement->status = EngagementStatusEnum::Idle;
        }

        // Cas : Facture en retard (overdue)
        elseif ($invoice->status === 'overdue') {
            $engagement->status = EngagementStatusEnum::ActionRequired;
        }

        // Cas : Facture suspendue
        elseif ($invoice->status === 'suspended') {
            $engagement->stage = EngagementStageEnum::Suspended;
            $engagement->status = null;
        }

        // Cas : Facture annulée
        elseif ($invoice->status === 'cancelled') {
            $engagement->stage = EngagementStageEnum::Lost;
            $engagement->status = null;
        }

        $engagement->save();
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }
}
