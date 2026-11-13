<?php

namespace Tests\Feature;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Models\RunRegistration;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use App\Services\InvoiceService;
use App\Models\RunRegistrationElement;
use App\Services\RunRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceDuplicationTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);
        InvoiceService::flushCache();

        $this->client = Client::factory()->create();
    }

    private function element(float $cost = 100): ProvisionElement
    {
        $provision = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);

        return ProvisionElement::factory()->create([
            'edition_id'     => $this->edition->id,
            'recipient_id'   => $this->client->id,
            'recipient_type' => Client::class,
            'provision_id'   => $provision->id,
            'has_product'    => true,
            'quantity'       => 1,
            'cost'           => $cost,
            'tax_rate'       => 8.1,
        ]);
    }

    public function test_provisions_are_not_invoiced_twice(): void
    {
        $this->element();
        $this->element();

        $first = InvoiceService::generateInvoiceByClient($this->client->id);

        $this->assertCount(2, $first->positions);
        $this->assertEquals(InvoiceStatusEnum::Ready, $first->status);

        $this->expectException(\RuntimeException::class);
        InvoiceService::generateInvoiceByClient($this->client->id);
    }

    public function test_added_provisions_go_on_a_new_invoice_when_previous_is_not_draft(): void
    {
        $this->element();
        $first = InvoiceService::generateInvoiceByClient($this->client->id);
        $first->update(['status' => InvoiceStatusEnum::Sent]);

        $this->element();
        $second = InvoiceService::generateInvoiceByClient($this->client->id);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertCount(1, $first->fresh()->positions);
        $this->assertCount(1, $second->positions);
    }

    public function test_draft_invoice_is_completed_not_duplicated(): void
    {
        $this->element();
        $draft = InvoiceService::generateInvoiceByClient($this->client->id);
        $draft->update(['status' => InvoiceStatusEnum::Draft]);

        $this->element();
        $same = InvoiceService::generateInvoiceByClient($this->client->id);

        $this->assertEquals($draft->id, $same->id);
        $this->assertCount(2, $same->fresh()->positions);
        $this->assertEquals(1, Invoice::count());
    }

    public function test_only_selected_provisions_are_invoiced(): void
    {
        $a = $this->element();
        $b = $this->element();

        $invoice = InvoiceService::generateInvoiceByClient($this->client->id, [$a->id]);

        $this->assertSame([$a->id], $invoice->provisionElementIds());
        $this->assertSame([$b->id], InvoiceService::uninvoicedProvisionElements($this->client->id)->pluck('id')->all());
    }

    public function test_cancelled_invoice_frees_its_provisions(): void
    {
        $this->element();
        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);
        $invoice->update(['status' => InvoiceStatusEnum::Cancelled]);

        $this->assertCount(1, InvoiceService::uninvoicedProvisionElements($this->client->id));
    }

    public function test_editing_a_provision_resyncs_a_draft_but_flags_a_ready_invoice(): void
    {
        $element = $this->element(100);
        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);

        // Prête : la facture ne bouge pas, la prestation est signalée « modifiée »
        $element->update(['cost' => 150]);
        $this->assertEquals(100, $invoice->fresh()->positions[0]['cost']);
        $this->assertSame('modified', InvoiceService::provisionElementInvoiceState($element->fresh())[0]);

        // Brouillon : la facture est resynchronisée
        $invoice->update(['status' => InvoiceStatusEnum::Draft]);
        $element->update(['cost' => 200]);
        $this->assertEquals(200, $invoice->fresh()->positions[0]['cost']);
        $this->assertSame('invoiced', InvoiceService::provisionElementInvoiceState($element->fresh())[0]);
    }

    public function test_legacy_invoice_without_element_ids_is_detected(): void
    {
        Invoice::create([
            'edition_id' => $this->edition->id,
            'client_id'  => $this->client->id,
            'status'     => InvoiceStatusEnum::Sent,
            'number'     => '2025-900',
            'positions'  => [['name' => 'Old', 'quantity' => 1, 'cost' => 10, 'tax_rate' => 8.1]],
        ]);

        $this->assertCount(1, InvoiceService::legacyProvisionInvoices($this->client->id));
    }

    public function test_run_invoice_does_not_overwrite_provision_invoice_nor_sent_invoice(): void
    {
        $this->element();
        $provisionInvoice = InvoiceService::generateInvoiceByClient($this->client->id);

        $run = Run::factory()->create(['cost' => 50]);
        $registration = RunRegistration::factory()->create(['client_id' => $this->client->id]);
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);

        $service = app(RunRegistrationService::class);
        $runInvoice = $service->createInvoiceForClient($this->client->id);

        $this->assertNotEquals($provisionInvoice->id, $runInvoice->id);
        $this->assertCount(1, $provisionInvoice->fresh()->positions);

        // Même facture envoyée : une nouvelle inscription crée une nouvelle facture
        $runInvoice->update(['status' => InvoiceStatusEnum::Sent]);
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);
        $second = $service->createInvoiceForClient($this->client->id);

        $this->assertNotEquals($runInvoice->id, $second->id);
        $this->assertEquals(1, $runInvoice->fresh()->positions[0]['quantity']);
        $this->assertEquals(1, $second->positions[0]['quantity']);

        // Plus rien de nouveau : erreur explicite
        $this->expectExceptionMessage('déjà facturées');
        $service->createInvoiceForClient($this->client->id);
    }

    public function test_draft_run_invoice_is_recomputed(): void
    {
        $run = Run::factory()->create(['cost' => 50]);
        $registration = RunRegistration::factory()->create(['client_id' => $this->client->id]);
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);

        $service = app(RunRegistrationService::class);
        $invoice = $service->createInvoiceForClient($this->client->id);
        $invoice->update(['status' => InvoiceStatusEnum::Draft]);

        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);
        $same = $service->createInvoiceForClient($this->client->id);

        $this->assertEquals($invoice->id, $same->id);
        $this->assertEquals(2, $same->fresh()->positions[0]['quantity']);
    }

    public function test_legacy_invoice_is_recognised_by_name_without_manual_work(): void
    {
        $a = $this->element(100);
        $b = $this->element(200);
        $c = $this->element(300);

        // Ancienne facture (sans identifiants) : A au même prix, B dont le prix a été modifié à la main
        Invoice::create([
            'edition_id' => $this->edition->id,
            'client_id'  => $this->client->id,
            'status'     => InvoiceStatusEnum::Sent,
            'number'     => '2025-901',
            'positions'  => [
                ['name' => $a->name, 'quantity' => 1, 'cost' => 100, 'tax_rate' => 8.1],
                ['name' => mb_strtoupper($b->name), 'quantity' => 1, 'cost' => 150, 'tax_rate' => 8.1],
            ],
        ]);

        $todo = InvoiceService::uninvoicedProvisionElements($this->client->id)->pluck('id')->all();

        $this->assertSame([$c->id], $todo);
        $this->assertSame('invoiced', InvoiceService::provisionElementInvoiceState($a->fresh())[0]);
        $this->assertSame('to_invoice', InvoiceService::provisionElementInvoiceState($c->fresh())[0]);
    }

    public function test_two_identical_provisions_are_matched_once_each(): void
    {
        $provision = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        foreach ([1, 2] as $_) {
            ProvisionElement::factory()->create([
                'edition_id'   => $this->edition->id, 'recipient_id' => $this->client->id, 'recipient_type' => Client::class,
                'provision_id' => $provision->id, 'has_product' => true, 'quantity' => 1, 'cost' => 50, 'tax_rate' => 8.1,
            ]);
        }

        // L'ancienne facture ne contient qu'une seule des deux
        Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => $this->client->id, 'status' => InvoiceStatusEnum::Sent,
            'number'     => '2025-902', 'positions' => [['name' => $provision->name, 'quantity' => 1, 'cost' => 50, 'tax_rate' => 8.1]],
        ]);

        $this->assertCount(1, InvoiceService::uninvoicedProvisionElements($this->client->id));
    }

    public function test_pro_forma_invoice_does_not_count_as_invoiced(): void
    {
        $this->element();
        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);
        $invoice->update(['is_pro_forma' => true]);

        $this->assertCount(1, InvoiceService::uninvoicedProvisionElements($this->client->id));
    }

    public function test_manual_invoice_is_never_blocked(): void
    {
        Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => $this->client->id, 'status' => InvoiceStatusEnum::Sent,
            'number'     => '2025-903', 'positions' => [['name' => 'Texte libre', 'quantity' => 1, 'cost' => 10, 'tax_rate' => 8.1]],
        ]);
        $this->element();

        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);

        $this->assertCount(1, $invoice->positions);
    }

    public function test_legacy_run_invoice_is_recognised_through_registration_invoice_id(): void
    {
        $run = Run::factory()->create(['cost' => 50]);
        $registration = RunRegistration::factory()->create(['client_id' => $this->client->id]);
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);

        $legacy = Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => $this->client->id, 'status' => InvoiceStatusEnum::Sent,
            'number'     => '2025-904', 'note' => 'Facture Inscriptions Courses regroupant les dossiers #1',
            'positions'  => [['name' => 'Trail (1 participant)', 'quantity' => 1, 'cost' => 50, 'tax_rate' => 8.1]],
        ]);
        $registration->update(['invoice_id' => $legacy->id]);

        $service = app(RunRegistrationService::class);

        // Rien de nouveau : pas de doublon
        try {
            $service->createInvoiceForClient($this->client->id);
            $this->fail('Une facture en doublon a été créée.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('déjà facturées', $e->getMessage());
        }

        // Nouvelle inscription : nouvelle facture, l\'ancienne reste intacte
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);
        $new = $service->createInvoiceForClient($this->client->id);

        $this->assertNotEquals($legacy->id, $new->id);
        $this->assertEquals(1, $new->positions[0]['quantity']);
        $this->assertEquals(50, $legacy->fresh()->positions[0]['cost']);
    }

    public function test_cancelled_run_invoice_is_ignored(): void
    {
        $run = Run::factory()->create(['cost' => 50]);
        $registration = RunRegistration::factory()->create(['client_id' => $this->client->id]);
        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false]);

        $service = app(RunRegistrationService::class);
        $first = $service->createInvoiceForClient($this->client->id);
        $first->update(['status' => InvoiceStatusEnum::Cancelled]);

        $second = $service->createInvoiceForClient($this->client->id);

        $this->assertNotEquals($first->id, $second->id);
        $this->assertEquals(InvoiceStatusEnum::Cancelled, $first->fresh()->status);
    }
}
