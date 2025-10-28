<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use App\Enums\ProvisionElementStatusEnum;

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
                'stage'  => EngagementStageEnum::Prospect,
                'status' => EngagementStatusEnum::Idle,
            ]);

            if ($engagement->wasRecentlyCreated) {
                $engagement->stage = EngagementStageEnum::ProposalSent;
                $engagement->save();
            }

            // Cas : Prestation confirmée
            if ($provisionElement->status === ProvisionElementStatusEnum::Confirmed) {
                $allConfirmed = ProvisionElement::where('recipient_id', $engagement->client_id)
                    ->where('recipient_type', 'App\Models\Client')
                    ->where('edition_id', $engagement->edition_id)
                    ->where('status', '!=', 'confirmed')
                    ->doesntExist();

                if ($allConfirmed) {
                    $engagement->stage = EngagementStageEnum::Confirmed;
                    $engagement->status = EngagementStatusEnum::Idle;
                    $engagement->save();
                }
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

        // Cas : Prestation à préparer
        if ($provisionElement->status === ProvisionElementStatusEnum::ToPrepare) {
            $allToPrepare = ProvisionElement::where('recipient_id', $engagement->client_id)
                ->where('recipient_type', 'App\\Models\\Client')
                ->where('edition_id', $engagement->edition_id)
                ->where('status', '!=', 'to_prepare')
                ->whereIn('status', [
                    'to_prepare', 'confirmed', 'ready',
                ])
                ->doesntExist();

            if ($allToPrepare) {
                $engagement->stage = EngagementStageEnum::Confirmed;
                $engagement->status = EngagementStatusEnum::ActionRequired;
            }
        }

        // Cas : Prestation confirmée
        if ($provisionElement->status === ProvisionElementStatusEnum::Confirmed) {
            $allConfirmed = ProvisionElement::where('recipient_id', $engagement->client_id)
                ->where('recipient_type', 'App\\Models\\Client')
                ->where('edition_id', $engagement->edition_id)
                ->where('status', '!=', 'confirmed')
                ->doesntExist();

            if ($allConfirmed) {
                $engagement->stage = EngagementStageEnum::Confirmed;
                $engagement->status = EngagementStatusEnum::Idle;
            }
        }

        // Cas : Paiement hors facture (donation)
        if ($provisionElement->isDirty('is_paid') && $provisionElement->is_paid === true) {
            $invoicesExist = Invoice::where('client_id', $engagement->client_id)
                ->where('edition_id', $engagement->edition_id)
                ->exists();

            if (! $invoicesExist) {
                $engagement->stage = EngagementStageEnum::Paid;
                $engagement->status = null;
            }
        }

        // Cas : Action requise
        elseif ($provisionElement->status === ProvisionElementStatusEnum::ActionRequired) {
            $engagement->status = EngagementStatusEnum::ActionRequired;
        }

        // Cas : À relancer
        elseif ($provisionElement->status === ProvisionElementStatusEnum::ToRelaunch) {
            $engagement->status = EngagementStatusEnum::ToRelaunch;
        }

        // Cas : À modifier
        elseif ($provisionElement->status === ProvisionElementStatusEnum::ToModify) {
            $engagement->status = EngagementStatusEnum::ToModify;
        }

        // Cas : Annulée
        elseif ($provisionElement->status === ProvisionElementStatusEnum::Cancelled) {
            $provisionsCount = ProvisionElement::where('recipient_id', $engagement->client_id)
                ->where('recipient_type', 'App\\Models\\Client')
                ->where('edition_id', $engagement->edition_id)
                ->count();

            if ($provisionsCount == 1) {
                $engagement->stage = EngagementStageEnum::Lost;
                $engagement->status = null;
            }
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
