<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Product;
use App\Models\Dicastry;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Models\ProvisionElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ElicDev\SiteProtection\Http\Middleware\SiteProtection;

uses(RefreshDatabase::class);

test('client provisions detailed report route works', function () {
    $this->withoutMiddleware([SiteProtection::class]);
    $user = User::factory()->create();
    $edition = Edition::factory()->create(['year' => 2026]);
    $category = ClientCategory::factory()->create();
    $client = Client::factory()->create(['category_id' => $category->id]);
    $provision = Provision::factory()->create([
        'edition_id'  => $edition->id,
        'dicastry_id' => Dicastry::factory(),
        'product_id'  => Product::factory(),
    ]);

    ProvisionElement::factory()->create([
        'recipient_id'   => $client->id,
        'recipient_type' => Client::class,
        'provision_id'   => $provision->id,
        'edition_id'     => $edition->id,
    ]);

    $response = $this->actingAs($user)->get(route('reports.client-provisions', ['edition' => 2026]));

    $response->assertStatus(200);
});

test('client provisions matrix report pdf route works', function () {
    $this->withoutMiddleware([SiteProtection::class]);
    $user = User::factory()->create();
    $edition = Edition::factory()->create(['year' => 2026]);
    $category = ClientCategory::factory()->create();
    $client = Client::factory()->create(['category_id' => $category->id]);
    $provision = Provision::factory()->create([
        'edition_id'  => $edition->id,
        'dicastry_id' => Dicastry::factory(),
        'product_id'  => Product::factory(),
    ]);

    ProvisionElement::factory()->create([
        'recipient_id'      => $client->id,
        'recipient_type'    => Client::class,
        'provision_id'      => $provision->id,
        'edition_id'        => $edition->id,
        'numeric_indicator' => 2,
    ]);

    $response = $this->actingAs($user)->get(route('reports.client-provisions-matrix', ['edition' => 2026]));

    $response->assertStatus(200);
});

test('client provisions matrix report excel export works', function () {
    $this->withoutMiddleware([SiteProtection::class]);
    $user = User::factory()->create();
    $edition = Edition::factory()->create(['year' => 2026]);
    $category = ClientCategory::factory()->create();
    $client = Client::factory()->create(['category_id' => $category->id]);
    $provision = Provision::factory()->create([
        'edition_id'  => $edition->id,
        'dicastry_id' => Dicastry::factory(),
        'product_id'  => Product::factory(),
        'name'        => 'Banner Test',
    ]);

    ProvisionElement::factory()->create([
        'recipient_id'      => $client->id,
        'recipient_type'    => Client::class,
        'provision_id'      => $provision->id,
        'edition_id'        => $edition->id,
        'numeric_indicator' => 3,
    ]);

    $response = $this->actingAs($user)->get(route('reports.client-provisions-matrix', ['edition' => 2026, 'export' => 1]));

    $response->assertStatus(200);
    $response->assertDownload('2026-client-provisions-matrice.xlsx');
});
