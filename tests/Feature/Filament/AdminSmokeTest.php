<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Provision;
use Filament\Facades\Filament;
use App\Models\ProvisionElement;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\ContactResource;
use App\Filament\Resources\ProvisionResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ProvisionElementResource;

/**
 * Charge chaque page du panel admin pour détecter toute erreur de rendu
 * (selects, filtres, colonnes, actions, groupes d'actions).
 */
class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $edition->id]);
        config(['cdn.default_edition_id' => $edition->id]);

        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel('admin');
    }

    public function test_every_resource_index_and_create_page_renders(): void
    {
        $resources = collect(Filament::getResources());
        $this->assertNotEmpty($resources);

        foreach ($resources as $resource) {
            $pages = $resource::getPages();

            foreach (['index', 'create'] as $page) {
                if (! isset($pages[$page])) {
                    continue;
                }

                $this->get($resource::getUrl($page))->assertSuccessful("{$resource} {$page}");
            }
        }
    }

    public function test_custom_pages_render(): void
    {
        foreach (Filament::getPages() as $page) {
            $this->get($page::getUrl())->assertSuccessful($page);
        }
    }

    public function test_edit_pages_render_for_existing_records(): void
    {
        $provision = Provision::factory()->create(['edition_id' => session('edition_id'), 'product_id' => null]);
        $client = Client::factory()->create();

        $records = [
            ClientResource::class           => $client,
            ContactResource::class          => Contact::factory()->create(),
            ProvisionResource::class        => $provision,
            ProvisionElementResource::class => ProvisionElement::factory()->create([
                'edition_id'     => session('edition_id'), 'recipient_id' => $client->id,
                'recipient_type' => Client::class, 'provision_id' => $provision->id,
            ]),
        ];

        foreach ($records as $resource => $record) {
            $this->get($resource::getUrl('edit', ['record' => $record]))->assertSuccessful($resource);
        }
    }
}
