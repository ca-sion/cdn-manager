<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $engagement = ClientEngagement::firstOrCreate([
            'client_id'  => $invoice->client_id,
            'edition_id' => $invoice->edition_id,
        ]);

        if ($engagement) {
            if ($invoice->status === InvoiceStatusEnum::Draft) {
                $engagement->stage = EngagementStageEnum::Billed;
                $engagement->status = EngagementStatusEnum::ToModify;
            } elseif ($invoice->status === InvoiceStatusEnum::Ready) {
                $engagement->stage = EngagementStageEnum::Billed;
                $engagement->status = EngagementStatusEnum::ToModify;
            }

            $engagement->save();
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $engagement = ClientEngagement::firstOrCreate([
            'client_id'  => $invoice->client_id,
            'edition_id' => $invoice->edition_id,
        ]);

        if (! $engagement || ! $invoice->isDirty('status')) {
            return;
        }

        // Cas : Facture payée
        if ($invoice->status === InvoiceStatusEnum::Paid) {
            $allInvoicesPaid = Invoice::where('client_id', $engagement->client_id)
                ->where('edition_id', $engagement->edition_id)
                ->where('status', '!=', 'paid')
                ->doesntExist();

            if ($allInvoicesPaid) {
                $engagement->stage = EngagementStageEnum::Paid;

                $pendingProvisions = ProvisionElement::where('recipient_id', $engagement->client_id)
                    ->where('recipient_type', 'App\\Models\\Client')
                    ->where('edition_id', $engagement->edition_id)
                    ->whereIn('status', [
                        'to_prepare', 'confirmed', 'ready', 'to_confirm', 'to_modify', 'action_required', 'to_relaunch',
                    ])
                    ->exists();

                if ($pendingProvisions) {
                    $engagement->status = EngagementStatusEnum::ActionRequired;
                } else {
                    $engagement->status = null;
                }
            }
        }

        // Cas : Facture en brouillon ou prête
        elseif ($invoice->status === InvoiceStatusEnum::Draft || $invoice->status === InvoiceStatusEnum::Ready) {
            $pendingProvisions = ProvisionElement::where('recipient_id', $engagement->client_id)
                ->where('recipient_type', 'App\\Models\\Client')
                ->where('edition_id', $engagement->edition_id)
                ->whereIn('status', [
                    'to_prepare', 'confirmed', 'ready', 'to_confirm', 'to_modify', 'action_required', 'to_relaunch',
                ])
                ->exists();
            if (! $pendingProvisions) {
                $engagement->stage = EngagementStageEnum::Billed;
                $engagement->status = EngagementStatusEnum::Idle;
            }
        }

        // Cas : Facture envoyée
        elseif ($invoice->status === InvoiceStatusEnum::Sent) {
            if (! $engagement->stage === EngagementStageEnum::Billed) {
                $engagement->stage = EngagementStageEnum::Billed;
            }
            $engagement->status = EngagementStatusEnum::Idle;
        }

        // Cas : Facture relancée
        elseif ($invoice->status === InvoiceStatusEnum::Relaunched) {
            $engagement->stage = EngagementStageEnum::Billed;
            $engagement->status = EngagementStatusEnum::Relaunched;
        }

        // Cas : Facture en retard (overdue)
        elseif ($invoice->status === InvoiceStatusEnum::Overdue) {
            $engagement->status = EngagementStatusEnum::ActionRequired;
        }

        // Cas : Facture avec action requise
        elseif ($invoice->status === InvoiceStatusEnum::ActionRequired) {
            $engagement->stage = EngagementStageEnum::Billed;
            $engagement->status = EngagementStatusEnum::ActionRequired;
        }

        // Cas : Facture suspendue
        elseif ($invoice->status === InvoiceStatusEnum::Suspended) {
            $engagement->stage = EngagementStageEnum::Billed;
            $engagement->status = null;
        }

        // Cas : Facture annulée
        elseif ($invoice->status === InvoiceStatusEnum::Cancelled) {
            $engagement->stage = EngagementStageEnum::Billed;
            $engagement->status = EngagementStatusEnum::Cancelled;
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
