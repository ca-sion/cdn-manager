<?php

namespace Tests\Unit\Services;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Services\RunRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RunRegistrationInvoiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RunRegistrationService $service;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RunRegistrationService;
        $this->edition = Edition::factory()->create(['year' => 2026]);
        session(['edition_id' => (string) $this->edition->id]);
    }

    /** @test */
    public function it_can_create_an_invoice_from_a_registration()
    {
        $client = Client::factory()->create();
        $registration = RunRegistration::factory()->create(['client_id' => $client->id, 'company_name' => 'Ma Boite']);

        $run = Run::factory()->create(['name' => 'Course 1', 'cost' => 50.00]);

        RunRegistrationElement::factory()->create([
            'run_registration_id'       => $registration->id,
            'run_id'                    => $run->id,
            'run_name'                  => 'Course 1',
            'has_free_registration_fee' => false,
        ]);

        $invoice = $this->service->createInvoice($registration);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($client->id, $invoice->client_id);
        $this->assertEquals(50.00, $invoice->total);
        $this->assertCount(1, $invoice->positions);
        $this->assertEquals('Course 1', $invoice->positions[0]['name']);
        $this->assertStringContainsString('Ma Boite', $invoice->title);
    }
}
