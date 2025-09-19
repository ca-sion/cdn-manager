<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\ProvisionElement;
use App\Models\ClientEngagement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;

class ProvisionElementObserver
{
    /**
     * Handle the ProvisionElement "created" event.
     */
    public function created(ProvisionElement $provisionElement): void
    {
        if ($provisionElement->recipient instanceof Client) {
            $engagement = ClientEngagement::firstOrCreate([
                'client_id'  => $provisionElement->recipient_id,
                'edition_id' => $provisionElement->edition_id,
            ], [
                'stage' => EngagementStageEnum::Prospect,
                'status' => EngagementStatusEnum::Idle
            ]);

            // Si c'est un nouvel engagement, on le fait progresser
            if ($engagement->wasRecentlyCreated) {
                $engagement->stage = EngagementStageEnum::ProposalSent;
                $engagement->save();
            }
        }
    }

    /**
     * Handle the ProvisionElement "updated" event.
     */
    public function updated(ProvisionElement $provisionElement): void
    {
        if (! $provisionElement->recipient instanceof Client) {
            return;
        }
        
        $engagement = ClientEngagement::where('client_id', $provisionElement->recipient_id)
                                      ->where('edition_id', $provisionElement->edition_id)
                                      ->first();

        if (! $engagement || ! $provisionElement->isDirty('status')) {
            return;
        }

        // Récupère toutes les prestations liées à cet engagement
        $provisions = $engagement->provisionElements;

        // Cas : Prestation confirmée
        if ($provisionElement->status === 'confirmed') {
            $allConfirmed = $provisions->every(fn($pe) => $pe->status === 'confirmed');
            if ($allConfirmed) {
                $engagement->stage = EngagementStageEnum::Confirmed;
                $engagement->status = EngagementStatusEnum::Idle;
            }
        }
        
        // Cas : Paiement hors facture (donation)
        if ($provisionElement->isDirty('is_paid') && $provisionElement->is_paid === true) {
            if ($engagement->invoices()->doesntExist()) {
                $engagement->stage = EngagementStageEnum::Paid;
                $engagement->status = null;
            }
        }

        // Cas : Action requise
        elseif ($provisionElement->status === 'action_required') {
            $engagement->status = EngagementStatusEnum::ActionRequired;
        }

        // Cas : À relancer
        elseif ($provisionElement->status === 'to_relaunch') {
            $engagement->status = EngagementStatusEnum::ToRelaunch;
        }

        // Cas : À modifier
        elseif ($provisionElement->status === 'to_modify') {
            $engagement->status = EngagementStatusEnum::ToModify;
        }

        // Cas : Annulée
        elseif ($provisionElement->status === 'cancelled') {
            $engagement->stage = EngagementStageEnum::Lost;
            $engagement->status = null;
        }

        $engagement->save();
    }

    /**
     * Handle the ProvisionElement "deleted" event.
     */
    public function deleted(ProvisionElement $provisionElement): void
    {
        //
    }

    /**
     * Handle the ProvisionElement "restored" event.
     */
    public function restored(ProvisionElement $provisionElement): void
    {
        //
    }

    /**
     * Handle the ProvisionElement "force deleted" event.
     */
    public function forceDeleted(ProvisionElement $provisionElement): void
    {
        //
    }
}
