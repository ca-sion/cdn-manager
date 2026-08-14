<?php

namespace Tests\Feature;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use Illuminate\Support\Facades\URL;
use App\Livewire\FrontRunRegistration;
use App\Models\RunRegistrationElement;
use App\Services\RunRegistrationService;
use App\Notifications\RunRegistrationLink;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RunRegistrationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Edition::firstOrCreate(['id' => 1], ['name' => '2026', 'year' => '2026']);
    }

    /** @test */
    public function public_creation_route_renders_form()
    {
        $response = $this->get(route('front.run-registration.create', ['type' => 'company']));
        $response->assertStatus(200);
    }

    /** @test */
    public function public_creation_submits_saves_and_dispatches_notification()
    {
        Notification::fake();

        $run = Run::factory()->create(['name' => 'Course 10km', 'cost' => 35.00]);

        Livewire::test(FrontRunRegistration::class, ['type' => 'company'])
            ->set('data.company_name', 'Acme Corp')
            ->set('data.contact_first_name', 'Jean')
            ->set('data.contact_last_name', 'Dupont')
            ->set('data.contact_email', 'jean.dupont@acme.test')
            ->set('data.contact_phone', '0791234567')
            ->set('data.invoicing_company_name', 'Acme Corp Holding')
            ->set('data.invoicing_email', 'facturation@acme.test')
            ->set('data.invoicing_address', 'Rue de la Gare 1')
            ->set('data.invoicing_postal_code', '1950')
            ->set('data.invoicing_locality', 'Sion')
            ->set('elements', [
                [
                    '_k'         => 'l1',
                    'first_name' => 'Pierre',
                    'last_name'  => 'Martin',
                    'birthdate'  => '1990-05-15',
                    'gender'     => 'M',
                    'email'      => 'pierre.martin@acme.test',
                    'run_id'     => $run->id,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $registration = RunRegistration::where('contact_email', 'jean.dupont@acme.test')->first();
        $this->assertNotNull($registration);
        $this->assertEquals('Acme Corp', $registration->company_name);
        $this->assertCount(1, $registration->runRegistrationElements);

        Notification::assertSentTo($registration, RunRegistrationLink::class);
    }

    /** @test */
    public function signed_edit_link_allows_editing()
    {
        $registration = RunRegistration::factory()->create([
            'run_registration_type' => RunRegistrationType::Company,
            'contact_first_name'    => 'Jean',
            'contact_last_name'     => 'Dupont',
            'contact_email'         => 'jean@test.ch',
        ]);

        $element = RunRegistrationElement::factory()->create([
            'run_registration_id' => $registration->id,
            'first_name'          => 'Alice',
            'last_name'           => 'Bernard',
        ]);

        $url = URL::signedRoute('front.run-registration.edit', [
            'registration' => $registration->id,
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);

        Livewire::test(FrontRunRegistration::class, ['registration' => $registration->id])
            ->assertSet('data.company_name', null)
            ->assertSet('data.contact_first_name', 'Jean')
            ->assertCount('elements', 1);
    }

    /** @test */
    public function unsigned_edit_link_is_forbidden()
    {
        $registration = RunRegistration::factory()->create();

        $url = route('front.run-registration.edit', [
            'registration' => $registration->id,
        ]);

        $response = $this->get($url);
        $response->assertStatus(403);
    }

    /** @test */
    public function laragrid_grid_ops_call_is_authorized()
    {
        $component = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);
        $elements = $component->get('elements');
        $rowKey = $elements[0]['_k'] ?? 'l1';

        $result = $component->call('gridOps', 'elements', [
            'baseVersion' => 0,
            'ops'         => [
                ['seq' => 1, 't' => 'set', 'row' => $rowKey, 'col' => 'first_name', 'v' => 'Alice'],
            ],
        ]);

        $result->assertHasNoErrors();
    }

    /** @test */
    public function create_invoice_service_generates_invoice_for_linked_client()
    {
        $client = Client::create([
            'name'  => 'Test Client SA',
            'email' => 'client@test.ch',
        ]);

        $run = Run::factory()->create(['name' => 'Trail 20km', 'cost' => 50.00]);

        $registration = RunRegistration::factory()->create([
            'client_id' => $client->id,
        ]);

        RunRegistrationElement::factory()->create([
            'run_registration_id'       => $registration->id,
            'run_id'                    => $run->id,
            'has_free_registration_fee' => false,
        ]);

        $service = app(RunRegistrationService::class);
        $invoice = $service->createInvoice($registration);

        $this->assertNotNull($invoice);
        $this->assertEquals($client->id, $invoice->client_id);
    }
}
