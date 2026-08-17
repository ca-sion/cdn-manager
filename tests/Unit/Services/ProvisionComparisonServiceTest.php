<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Models\ProvisionElement;
use App\Services\ProvisionComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionComparisonServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_compare_editions_without_category_filter(): void
    {
        $refEdition = Edition::factory()->create(['year' => 2025]);
        $compEdition = Edition::factory()->create(['year' => 2024]);

        $provision = Provision::factory()->create([
            'edition_id' => $refEdition->id,
            'product_id' => null,
        ]);

        $categoryA = ClientCategory::factory()->create(['name' => 'Category A']);
        $categoryB = ClientCategory::factory()->create(['name' => 'Category B']);

        $clientA = Client::factory()->create(['category_id' => $categoryA->id, 'name' => 'Client A']);
        $clientB = Client::factory()->create(['category_id' => $categoryB->id, 'name' => 'Client B']);

        ProvisionElement::factory()->create([
            'provision_id'   => $provision->id,
            'recipient_id'   => $clientA->id,
            'recipient_type' => Client::class,
            'edition_id'     => $refEdition->id,
        ]);

        ProvisionElement::factory()->create([
            'provision_id'   => $provision->id,
            'recipient_id'   => $clientB->id,
            'recipient_type' => Client::class,
            'edition_id'     => $compEdition->id,
        ]);

        $service = new ProvisionComparisonService;
        $result = $service->compareEditions($refEdition, $compEdition);

        $this->assertCount(1, $result['new']);
        $this->assertEquals($clientA->id, $result['new']->first()->id);
        $this->assertCount(1, $result['lost']);
        $this->assertEquals($clientB->id, $result['lost']->first()->id);
    }

    public function test_it_can_filter_by_multiple_client_categories(): void
    {
        $refEdition = Edition::factory()->create(['year' => 2025]);
        $compEdition = Edition::factory()->create(['year' => 2024]);

        $provision = Provision::factory()->create([
            'edition_id' => $refEdition->id,
            'product_id' => null,
        ]);

        $cat1 = ClientCategory::factory()->create(['name' => 'Cat 1']);
        $cat2 = ClientCategory::factory()->create(['name' => 'Cat 2']);
        $cat3 = ClientCategory::factory()->create(['name' => 'Cat 3']);

        $client1 = Client::factory()->create(['category_id' => $cat1->id, 'name' => 'Client 1']);
        $client2 = Client::factory()->create(['category_id' => $cat2->id, 'name' => 'Client 2']);
        $client3 = Client::factory()->create(['category_id' => $cat3->id, 'name' => 'Client 3']);

        foreach ([$client1, $client2, $client3] as $client) {
            ProvisionElement::factory()->create([
                'provision_id'   => $provision->id,
                'recipient_id'   => $client->id,
                'recipient_type' => Client::class,
                'edition_id'     => $refEdition->id,
            ]);
        }

        $service = new ProvisionComparisonService;

        // Pass array of category IDs (cat1 and cat2)
        $result = $service->compareEditions($refEdition, $compEdition, [$cat1->id, $cat2->id]);

        $newClientIds = $result['new']->pluck('id')->all();
        $this->assertContains($client1->id, $newClientIds);
        $this->assertContains($client2->id, $newClientIds);
        $this->assertNotContains($client3->id, $newClientIds);
    }

    public function test_it_accepts_single_integer_for_backwards_compatibility(): void
    {
        $refEdition = Edition::factory()->create(['year' => 2025]);
        $compEdition = Edition::factory()->create(['year' => 2024]);

        $provision = Provision::factory()->create([
            'edition_id' => $refEdition->id,
            'product_id' => null,
        ]);

        $cat1 = ClientCategory::factory()->create(['name' => 'Cat 1']);
        $cat2 = ClientCategory::factory()->create(['name' => 'Cat 2']);

        $client1 = Client::factory()->create(['category_id' => $cat1->id, 'name' => 'Client 1']);
        $client2 = Client::factory()->create(['category_id' => $cat2->id, 'name' => 'Client 2']);

        foreach ([$client1, $client2] as $client) {
            ProvisionElement::factory()->create([
                'provision_id'   => $provision->id,
                'recipient_id'   => $client->id,
                'recipient_type' => Client::class,
                'edition_id'     => $refEdition->id,
            ]);
        }

        $service = new ProvisionComparisonService;
        $result = $service->compareEditions($refEdition, $compEdition, $cat1->id);

        $newClientIds = $result['new']->pluck('id')->all();
        $this->assertContains($client1->id, $newClientIds);
        $this->assertNotContains($client2->id, $newClientIds);
    }
}
