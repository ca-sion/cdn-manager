<?php

namespace App\Services;

use App\Models\Run;
use App\Models\RunRegistration;

class RunRegistrationService
{
    /**
     * Calcule le montant total d'une inscription en sommant le coût des courses liées.
     * Les éléments marqués avec 'has_free_registration_fee' ne sont pas comptabilisés.
     */
    public function calculateTotal(RunRegistration $registration): float
    {
        return $registration->runRegistrationElements()
            ->where('has_free_registration_fee', false)
            ->get()
            ->sum(function ($element) {
                return $element->run?->cost ?? 0;
            });
    }

    /**
     * Vérifie si l'inscription à une course est toujours ouverte.
     */
    public function isRegistrationOpen(Run $run): bool
    {
        if (!$run->registrations_deadline) {
            return true;
        }

        return now()->lessThanOrEqualTo($run->registrations_deadline);
    }
}
