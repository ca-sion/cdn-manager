<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Créer les engagements pour chaque client et chaque édition où une prestation ou une facture existe
        $clientEditions = collect();
        $provisions = ProvisionElement::distinct()->select('client_id', 'edition_id')->get();
        $invoices = Invoice::distinct()->select('client_id', 'edition_id')->get();

        $clientEditions = $provisions->merge($invoices)->unique(function ($item) {
            return $item['client_id'].'-'.$item['edition_id'];
        });

        foreach ($clientEditions as $pair) {
            ClientEngagement::firstOrCreate([
                'client_id'  => $pair->client_id,
                'edition_id' => $pair->edition_id,
            ]);
        }

        // 2. Parcourir tous les engagements pour déduire le stage et les autres valeurs
        $engagements = ClientEngagement::all();
        foreach ($engagements as $engagement) {
            $invoices = Invoice::where('client_id', $engagement->client_id)->where('edition_id', $engagement->edition_id)->get();
            $provisions = ProvisionElement::where('client_id', $engagement->client_id)->where('edition_id', $engagement->edition_id)->get();

            // DEDUIRE le stage de l'engagement (le plus avancé en premier)
            if ($invoices->contains('status', 'payed')) {
                $engagement->stage = EngagementStageEnum::Paid;
            } elseif ($invoices->contains('status', 'sent')) {
                $engagement->stage = EngagementStageEnum::Billed;
            } elseif ($provisions->contains('status', 'approved') || $provisions->contains('status', 'confirmed')) {
                $engagement->stage = EngagementStageEnum::Confirmed;
            } elseif ($provisions->contains('status', 'sent') || $provisions->contains('status', 'sent_by_post')) {
                $engagement->stage = EngagementStageEnum::ProposalSent;
            } elseif ($provisions->contains('status', 'to_prepare')) {
                $engagement->stage = EngagementStageEnum::Prospect;
            }

            // DEDUIRE le status temporaire de l'engagement (le plus urgent en premier)
            if ($provisions->contains('status', 'action_required') || $invoices->contains('status', 'action_required')) {
                $engagement->status = EngagementStatusEnum::ActionRequired;
            } elseif ($provisions->contains('status', 'to_relaunch') || $provisions->contains('status', 'relaunched') || $invoices->contains('status', 'to_relaunch') || $invoices->contains('status', 'relaunched')) {
                $engagement->status = EngagementStatusEnum::ToRelaunch;
            } elseif ($provisions->contains('status', 'to_modify') || $invoices->contains('status', 'to_modify')) {
                $engagement->status = EngagementStatusEnum::ToModify;
            } elseif ($invoices->contains('status', 'overdue')) {
                $engagement->status = EngagementStatusEnum::ActionRequired;
            } elseif ($provisions->contains('status', 'suspended') || $invoices->contains('status', 'suspended')) {
                $engagement->stage = EngagementStageEnum::Suspended;
            } elseif ($provisions->contains('status', 'cancelled') || $invoices->contains('status', 'cancelled')) {
                $engagement->stage = EngagementStageEnum::Lost;
            } else {
                $engagement->status = EngagementStatusEnum::Idle;
            }

            // Si l'engagement est payé, toutes les prestations sont payées
            if ($engagement->stage === EngagementStageEnum::Paid && $invoices->isEmpty()) {
                $provisions->each(function ($provision) {
                    $provision->is_paid = true;
                    $provision->save();
                });
            }

            $engagement->save();

            // 3. Mettre à jour les nouvelles colonnes sur les tables existantes
            foreach ($invoices as $invoice) {
                if ($invoice->status === 'sent_by_post') {
                    $invoice->delivery_method = 'post';
                }
                $invoice->save();
            }

            foreach ($provisions as $provision) {
                if ($provision->status === 'sent_by_post') {
                    $provision->delivery_method = 'post';
                }
                if ($provision->status === 'made_by') {
                    // Si un responsable texte est déjà défini, on ajoute une note.
                    if (! empty($provision->responsible)) {
                        $provision->note = $provision->note.' | Ancien statut: MadeBy.';
                    } else {
                        // On met à jour le champ responsible avec la valeur de dicastry_id si possible
                        // (nécessite une logique pour retrouver le nom du dicastry)
                        $provision->note = $provision->note.' | A été marqué comme "MadeBy". A besoin d\'être vérifié pour le responsable. Ancien statut: MadeBy.';
                    }
                    $provision->status = 'done';
                }
                $provision->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration de données est irréversible.
    }
};
