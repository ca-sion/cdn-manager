<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEngagement;
use Illuminate\Support\Facades\DB;

class ClientMergeService
{
    public function merge(Client $primaryClient, Client $secondaryClient, array $data, Client $clientA, Client $clientB): void
    {
        DB::transaction(function () use ($primaryClient, $secondaryClient, $data, $clientA, $clientB) {
            // 1. Update primary client's attributes
            $attributesToUpdate = [];
            foreach ($data as $key => $value) {
                if (in_array($key, ['primary_client_id', '_token', 'note', 'invoicing_note'])) {
                    continue;
                }

                $chosenClient = ($value === 'A') ? $clientA : $clientB;
                $attributesToUpdate[$key] = $chosenClient->$key;
            }
            $attributesToUpdate['note'] = $data['note'];
            $attributesToUpdate['invoicing_note'] = $data['invoicing_note'];
            $primaryClient->update($attributesToUpdate);

            // 2. Re-associate related models
            $secondaryClient->invoices()->update(['client_id' => $primaryClient->id]);
            $secondaryClient->documents()->update(['client_id' => $primaryClient->id]);
            $secondaryClient->provisionElements()->update(['recipient_id' => $primaryClient->id]);

            // 3. Handle Many-to-Many for Contacts
            $secondaryContacts = $secondaryClient->contacts()->pluck('contacts.id');
            $primaryClient->contacts()->syncWithoutDetaching($secondaryContacts);

            // 4. Handle ClientEngagements with unique constraint
            $secondaryEngagements = $secondaryClient->clientEngagements()->get();
            if ($secondaryEngagements) {
                foreach ($secondaryEngagements as $secondaryEngagement) {
                    $primaryEngagement = ClientEngagement::where('client_id', $primaryClient->id)
                        ->where('edition_id', $secondaryEngagement->edition_id)
                        ->first();
                    if ($primaryEngagement) {
                        // If conflict, skip to not overwrite primary's engagement
                        continue;
                    }
                    $secondaryEngagement->update(['client_id' => $primaryClient->id]);
                }
            }

            // 5. Move Media
            $secondaryClient->getMedia('logos')->each(function ($media) use ($primaryClient) {
                $media->move($primaryClient, 'logos');
            });

            // 6. Delete secondary client
            $secondaryClient->delete();
        });
    }
}