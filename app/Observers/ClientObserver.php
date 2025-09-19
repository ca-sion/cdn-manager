<?php

namespace App\Observers;

use App\Models\Client;
use App\Enums\EngagementStageEnum;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        // Créer l'engagement du client pour l'édition actuelle.
        $engagement = $client->currentEngagement()->firstOrCreate([
            'edition_id' => session()->get('edition_id') ?? setting('edition_id'),
        ]);
        $engagement->stage = EngagementStageEnum::Prospect;
        $engagement->save();
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
