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
        // 1. Convertir les anciennes valeurs des ENUM vers les nouvelles.

        // Conversion des statuts des factures
        Invoice::where('status', 'payed')->update(['status' => 'paid']);
        Invoice::where('status', 'sent_by_post')->update(['status' => 'sent', 'delivery_method' => 'post']);
        Invoice::where('status', 'to_relaunch')->update(['status' => 'relaunched']);

        // Conversion des statuts des prestations
        ProvisionElement::where('status', 'to_contact')->update(['status' => 'to_prepare']);
        ProvisionElement::where('status', 'contacted')->update(['status' => 'to_prepare']);
        ProvisionElement::where('status', 'sent')->update(['status' => 'confirmed']);
        ProvisionElement::where('status', 'sent_by_post')->update(['status' => 'confirmed', 'delivery_method' => 'post']);
        // ProvisionElement::where('status', 'received')->update(['status' => 'done']);
        ProvisionElement::where('status', 'approved')->update(['status' => 'confirmed']);
        ProvisionElement::where('status', 'relaunched')->update(['status' => 'to_relaunch']);

        // Logique spécifique pour MadeBy
        $madeByProvisions = ProvisionElement::where('status', 'made_by')->get();
        foreach ($madeByProvisions as $provision) {
            $note = $provision->note ?? '';
            $newNote = $note.' | Ancien statut: MadeBy. A besoin d\'être vérifié pour le responsable.';
            $provision->update([
                'status' => 'done',
                'note'  => $newNote,
            ]);
        }

        // 2. Créer les engagements pour chaque client et chaque édition où une prestation ou une facture existe
        $clientEditions = collect();
        $provisions = ProvisionElement::distinct()->select('recipient_id as client_id', 'edition_id', 'recipient_type')->where('recipient_type', 'App\\Models\\Client')->get();
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

        // 3. Parcourir tous les engagements pour déduire le stage et le statut
        $engagements = ClientEngagement::all();
        foreach ($engagements as $engagement) {
            // Trouver les factures et prestations associées à cet engagement en interrogeant les tables directement
            $invoices = Invoice::where('client_id', $engagement->client_id)
                ->where('edition_id', $engagement->edition_id)
                ->get();

            $provisions = ProvisionElement::where('recipient_id', $engagement->client_id)
                ->where('recipient_type', 'App\\Models\\Client')
                ->where('edition_id', $engagement->edition_id)
                ->get();

            // DÉDUCTION du STAGE de l'engagement (le plus avancé en premier)
            if ($invoices->contains('status', 'paid')) {
                $engagement->stage = EngagementStageEnum::Paid;
            } elseif ($invoices->contains('status', 'sent')) {
                $engagement->stage = EngagementStageEnum::Billed;
            } elseif ($provisions->contains('status', 'confirmed')) {
                $engagement->stage = EngagementStageEnum::Confirmed;
            } elseif ($provisions->contains('status', 'to_prepare')) {
                $engagement->stage = EngagementStageEnum::ProposalSent; // Le premier stade après prospect, car une proposition est préparée
            }

            // DÉDUCTION du STATUT de l'engagement (le plus urgent en premier)
            $engagement->status = EngagementStatusEnum::Idle; // Valeur par défaut

            if ($invoices->contains('status', 'overdue') || $provisions->contains('status', 'action_required') || $invoices->contains('status', 'action_required')) {
                $engagement->status = EngagementStatusEnum::ActionRequired;
            } elseif ($provisions->contains('status', 'to_relaunch')) {
                $engagement->status = EngagementStatusEnum::ToRelaunch;
            } elseif ($provisions->contains('status', 'to_modify')) {
                $engagement->status = EngagementStatusEnum::ToModify;
            } elseif ($invoices->contains('status', 'suspended') || $provisions->contains('status', 'suspended')) {
                $engagement->stage = EngagementStageEnum::Suspended;
                $engagement->status = null;
            } elseif ($invoices->contains('status', 'cancelled') || $provisions->contains('status', 'cancelled')) {
                $engagement->stage = EngagementStageEnum::Lost;
                $engagement->status = null;
            }

            // Si l'engagement est payé, toutes les prestations non facturées sont payées
            if ($engagement->stage === EngagementStageEnum::Paid) {
                ProvisionElement::where('recipient_id', $engagement->client_id)
                    ->where('recipient_type', 'App\\Models\\Client')
                    ->where('edition_id', $engagement->edition_id)
                    ->where('is_paid', false)
                    ->each(function ($provision) {
                        $invoicesExist = Invoice::where('client_id', $provision->recipient_id)
                            ->where('edition_id', $provision->edition_id)
                            ->exists();
                        if (! $invoicesExist) {
                            $provision->is_paid = true;
                            $provision->save();
                        }
                    });

                // Si le stage est payé, le statut est nul sauf si des provisions sont en attente
                $pendingProvisions = ProvisionElement::where('recipient_id', $engagement->client_id)
                    ->where('recipient_type', 'App\\Models\\Client')
                    ->where('edition_id', $engagement->edition_id)
                    ->whereIn('status', [
                        'to_prepare', 'confirmed', 'ready', 'to_confirm', 'to_modify', 'action_required', 'to_relaunch',
                    ])->exists();

                if (! $pendingProvisions) {
                    $engagement->status = null;
                }
            }

            $engagement->save();

            // 4. Convertir les anciennes valeurs restantes des ENUM vers les nouvelles.
            Invoice::where('status', 'to_modify')->update(['status' => 'action_required']);

            ProvisionElement::where('status', 'to_confirm')->update(['status' => 'to_prepare']);
            ProvisionElement::where('status', 'to_modify')->update(['status' => 'to_prepare']);
            ProvisionElement::where('status', 'to_relaunch')->update(['status' => 'to_prepare']);
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
