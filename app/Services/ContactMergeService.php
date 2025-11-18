<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactMergeService
{
    public function merge(Contact $primaryContact, Contact $secondaryContact, array $data, Contact $contactA, Contact $contactB)
    {
        DB::transaction(function () use ($primaryContact, $secondaryContact, $data, $contactA, $contactB) {
            // Determine which contact's data to use for each field
            $fieldsToMerge = [
                'first_name', 'last_name', 'email', 'phone', 'role', 'department',
                'address', 'address_extension', 'postal_code', 'locality',
                'country_code', 'salutation', 'language', 'category_id',
            ];

            foreach ($fieldsToMerge as $field) {
                if (isset($data[$field])) {
                    $sourceContact = ($data[$field] === 'A') ? $contactA : $contactB;
                    $primaryContact->$field = $sourceContact->$field;
                }
            }

            // Merge notes if they exist on the model (assuming a 'note' field, add if necessary)
            if (isset($data['note'])) {
                $primaryContact->note = $data['note'];
            }

            $primaryContact->save();

            // Re-associate related models from the secondary contact
            // 1. Clients (many-to-many)
            $secondaryClients = $secondaryContact->clients()->pluck('clients.id');
            $primaryContact->clients()->syncWithoutDetaching($secondaryClients);

            // 2. Provision Elements (polymorphic one-to-many)
            $secondaryContact->provisionElements()->update(['recipient_id' => $primaryContact->id]);

            // Finally, delete the secondary contact
            $secondaryContact->forceDelete();
        });
    }
}
