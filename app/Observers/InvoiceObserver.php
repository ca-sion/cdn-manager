<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Services\InvoiceService;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;

class InvoiceObserver
{
    /**
     * Quand true, le suivi du client n'est pas synchronisé (restauration après « Annuler ces factures »).
     */
    public static bool $muted = false;

    /**
     * Le client a-t-il une AUTRE facture réelle (ni annulée, ni proforma) pour cette édition ?
     */
    protected function hasOtherActiveInvoice(Invoice $invoice): bool
    {
        return Invoice::withoutGlobalScopes()
            ->where('client_id', $invoice->client_id)
            ->where('edition_id', $invoice->edition_id)
            ->where('id', '!=', $invoice->id)
            ->where('status', '!=', InvoiceStatusEnum::Cancelled->value)
            ->where(fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma'))
            ->exists();
    }

    /**
     * Le client a-t-il encore une facture réelle impayée ? Les factures annulées et les proformas ne comptent pas ;
     * l'ancien statut « Payé (old) » compte comme payé.
     */
    protected function hasUnpaidInvoice(ClientEngagement $engagement): bool
    {
        return Invoice::withoutGlobalScopes()
            ->where('client_id', $engagement->client_id)
            ->where('edition_id', $engagement->edition_id)
            ->whereNotIn('status', [InvoiceStatusEnum::Cancelled->value, InvoiceStatusEnum::Paid->value, InvoiceStatusEnum::Payed->value])
            ->where(fn ($q) => $q->where('is_pro_forma', false)->orWhereNull('is_pro_forma'))
            ->exists();
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        InvoiceService::flushCache();

        if (self::$muted) {
            return;
        }

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
        InvoiceService::flushCache();

        if (self::$muted || ! $invoice->wasChanged('status')) {
            return;
        }

        $engagement = ClientEngagement::firstOrCreate([
            'client_id'  => $invoice->client_id,
            'edition_id' => $invoice->edition_id,
        ]);

        if (! $engagement) {
            return;
        }

        // Cas : Facture payée
        if ($invoice->status === InvoiceStatusEnum::Paid) {
            $allInvoicesPaid = ! $this->hasUnpaidInvoice($engagement);

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
            // Envoyer une facture = le client a (de nouveau) quelque chose d'impayé, même s'il était « Payé »
            $engagement->stage = EngagementStageEnum::Billed;
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
            // Le client a encore une autre facture active : son suivi n'est pas touché
            if ($this->hasOtherActiveInvoice($invoice)) {
                return;
            }

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
        InvoiceService::flushCache();
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
