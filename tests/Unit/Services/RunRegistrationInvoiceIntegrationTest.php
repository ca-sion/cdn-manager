<?php

namespace Tests\Unit\Services;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Models\RunRegistration;
use App\Models\ProvisionElement;
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
        $this->assertStringContainsString('Course 1', $invoice->positions[0]['name']);
        $this->assertNotEmpty($invoice->title);
    }

    /** @test */
    public function it_automatically_deducts_client_cdn_quota_when_creating_invoice()
    {
        $cdnProvision = Provision::create([
            'name'       => 'Inscription Entreprise CDN',
            'code'       => 'PROV-CDN-INVOICE',
            'is_active'  => true,
            'edition_id' => $this->edition->id,
        ]);

        setting([
            'edition_id'             => $this->edition->id,
            'voucher_cdn_provisions' => [$cdnProvision->id],
        ]);

        $client = Client::factory()->create(['name' => 'Banque UBS']);

        ProvisionElement::create([
            'edition_id'        => $this->edition->id,
            'provision_id'      => $cdnProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $client->id,
            'numeric_indicator' => 2,
        ]);

        $registration = RunRegistration::factory()->create(['client_id' => $client->id, 'company_name' => 'Banque UBS']);
        $run = Run::factory()->create(['name' => 'Course Entreprises', 'cost' => 40.00]);

        // 5 coureurs enregistrés
        for ($i = 0; $i < 5; $i++) {
            RunRegistrationElement::factory()->create([
                'run_registration_id'       => $registration->id,
                'run_id'                    => $run->id,
                'run_name'                  => 'Course Entreprises',
                'has_free_registration_fee' => false,
            ]);
        }

        $invoice = $this->service->createInvoiceForClient($client->id);

        $elements = $registration->fresh()->runRegistrationElements;
        $this->assertEquals(2, $elements->where('has_free_registration_fee', true)->count());
        $this->assertEquals(3, $elements->where('has_free_registration_fee', false)->count());

        // Facture : 3 payants * 40 = 120 CHF (au lieu de 200 CHF)
        $this->assertEquals(120.00, $invoice->total);
        $this->assertCount(2, $invoice->positions);
    }
}
