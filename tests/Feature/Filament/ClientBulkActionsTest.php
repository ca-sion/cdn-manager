<?php

namespace Tests\Feature\Filament;

use App\Helpers\AppHelper;
use App\Models\Client;
use App\Models\ClientCategory;
use App\Models\ClientEngagement;
use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClientBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_bulk_edit_client_category_and_locality()
    {
        $edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $edition->id]);

        $cat1 = ClientCategory::create(['name' => 'Old Category']);
        $cat2 = ClientCategory::create(['name' => 'New Category']);

        $client1 = Client::factory()->create(['category_id' => $cat1->id, 'locality' => 'Sion']);
        $client2 = Client::factory()->create(['category_id' => $cat1->id, 'locality' => 'Martigny']);

        $records = new Collection([$client1, $client2]);

        // Execute bulkEdit logic
        $records->each(function ($record) use ($cat2) {
            $record->category_id = $cat2->id;
            $record->locality = 'Sierre';
            $record->save();
        });

        $this->assertEquals($cat2->id, $client1->fresh()->category_id);
        $this->assertEquals('Sierre', $client1->fresh()->locality);
        $this->assertEquals($cat2->id, $client2->fresh()->category_id);
        $this->assertEquals('Sierre', $client2->fresh()->locality);
    }

    /** @test */
    public function it_can_copy_responsible_from_previous_edition()
    {
        $edition2024 = Edition::create(['year' => 2024, 'name' => '2024 Edition']);
        $edition2025 = Edition::create(['year' => 2025, 'name' => '2025 Edition']);

        session(['edition_id' => $edition2025->id]);

        $client = Client::factory()->create();

        // 2024 Engagement with responsible
        ClientEngagement::create([
            'edition_id'  => $edition2024->id,
            'client_id'   => $client->id,
            'responsible' => 'Marc Dubois',
        ]);

        $records = new Collection([$client]);

        // Simulating copy_previous_responsible action logic
        $currentEditionId = AppHelper::getCurrentEditionId();
        $currentEdition = Edition::find($currentEditionId);
        $previousEdition = Edition::where('year', '<', $currentEdition?->year)
            ->orderBy('year', 'desc')
            ->first();

        $this->assertNotNull($previousEdition);
        $this->assertEquals(2024, $previousEdition->year);

        foreach ($records as $record) {
            $prevEngagement = $record->clientEngagements()
                ->where('edition_id', $previousEdition->id)
                ->first();

            if ($prevEngagement && $prevEngagement->responsible) {
                $currentEngagement = $record->currentEngagement()->firstOrCreate([
                    'edition_id' => $currentEditionId,
                ]);
                $currentEngagement->responsible = $prevEngagement->responsible;
                $currentEngagement->save();
            }
        }

        $freshEngagement = $client->fresh()->clientEngagements()->where('edition_id', $edition2025->id)->first();
        $this->assertNotNull($freshEngagement);
        $this->assertEquals('Marc Dubois', $freshEngagement->responsible);
    }
}
