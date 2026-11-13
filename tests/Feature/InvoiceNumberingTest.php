<?php

namespace Tests\Feature;

use App\Models\Run;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
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
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;

class InvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);
        InvoiceService::flushCache();
    }

    private function clientWithProvision(): Client
    {
        $client = Client::factory()->create();
        ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
            'provision_id' => Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null])->id,
            'has_product'  => true, 'quantity' => 1, 'cost' => 100, 'tax_rate' => 8.1,
        ]);

        return $client;
    }

    private function clientWithRegistration(): Client
    {
        $client = Client::factory()->create();
        $registration = RunRegistration::factory()->create(['client_id' => $client->id]);
        RunRegistrationElement::factory()->create([
            'run_registration_id' => $registration->id, 'run_id' => Run::factory()->create(['cost' => 50])->id, 'has_free_registration_fee' => false,
        ]);

        return $client;
    }

    public function test_invoices_created_within_the_same_second_get_distinct_numbers(): void
    {
        $this->freezeTime(); // même seconde pour toutes les factures : cas du bulk

        $numbers = collect(range(1, 30))->map(fn () => InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id)->number);

        $this->assertCount(30, $numbers->unique(), 'doublon de numéro : '.$numbers->duplicates()->implode(', '));
    }

    public function test_provision_and_registration_invoices_share_one_sequence_without_collision(): void
    {
        $this->freezeTime();
        $service = app(RunRegistrationService::class);

        $numbers = collect();
        foreach (range(1, 10) as $_) {
            $numbers->push(InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id)->number);
            $numbers->push($service->createInvoiceForClient($this->clientWithRegistration()->id)->number);
        }

        $this->assertCount(20, $numbers->unique());
        $this->assertSame(Invoice::count(), Invoice::distinct('number')->count('number'));
    }

    public function test_deleted_invoice_numbers_are_not_reused_below_the_maximum(): void
    {
        $a = InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id);
        $b = InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id);
        $a->delete(); // supprimer une facture ancienne ne libère pas son numéro pour la suivante

        $c = InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id);

        $this->assertNotSame($b->number, $c->number);
        $this->assertGreaterThan((int) $b->number, (int) $c->number);
    }

    public function test_numbering_continues_after_existing_numbers_of_the_year_and_ignores_other_formats(): void
    {
        foreach (['2025007', '2025-900', '2024099'] as $number) {
            Invoice::create(['edition_id' => $this->edition->id, 'client_id' => Client::factory()->create()->id, 'status' => InvoiceStatusEnum::Paid, 'number' => $number, 'positions' => []]);
        }

        $this->assertSame('2025008', InvoiceService::generateInvoiceNumber());
    }

    public function test_title_and_qr_reference_follow_the_number(): void
    {
        $invoice = InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id);

        $this->assertSame('Facture '.$invoice->number, $invoice->title);
        $this->assertStringContainsString($invoice->number, $invoice->qr_reference);
    }

    public function test_bulk_generation_of_both_families_never_duplicates_a_number(): void
    {
        $this->actingAs(User::factory()->create());
        $this->freezeTime();
        $provisionClients = collect(range(1, 15))->map(fn () => $this->clientWithProvision());
        $runClients = collect(range(1, 15))->map(fn () => $this->clientWithRegistration());

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', $provisionClients->merge($runClients)->all(), data: [
                'provision_client_ids'    => $provisionClients->pluck('id')->all(),
                'registration_client_ids' => $runClients->pluck('id')->all(),
            ]);

        $this->assertSame(30, Invoice::count());
        $this->assertSame(30, Invoice::distinct('number')->count('number'));
    }

    public function test_invoice_form_rejects_an_already_used_number(): void
    {
        $this->actingAs(User::factory()->create());
        $existing = InvoiceService::generateInvoiceByClient($this->clientWithProvision()->id);
        $client = Client::factory()->create();

        Livewire::test(CreateInvoice::class)
            ->fillForm(['client_id' => $client->id, 'number' => $existing->number, 'title' => 'x'])
            ->call('create')
            ->assertHasFormErrors(['number' => 'unique']);
    }

    // ───────────── Commande de réparation ─────────────

    private function duplicate(string $number, InvoiceStatusEnum $status, string $title): Invoice
    {
        return Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => Client::factory()->create()->id, 'status' => $status,
            'number'     => $number, 'title' => $title, 'qr_reference' => '99999'.$number, 'positions' => [],
        ]);
    }

    public function test_repair_command_dry_run_changes_nothing(): void
    {
        $this->duplicate('2025002', InvoiceStatusEnum::Sent, 'Facture 2025002');
        $this->duplicate('2025002', InvoiceStatusEnum::Ready, 'Facture 2025002');

        $this->artisan('invoices:fix-duplicate-numbers')
            ->expectsOutputToContain('serait renumérotée')
            ->assertSuccessful();

        $this->assertSame(2, Invoice::where('number', '2025002')->count());
    }

    public function test_repair_command_renumbers_unsent_duplicates_and_keeps_the_sent_one(): void
    {
        $sent = $this->duplicate('2025002', InvoiceStatusEnum::Sent, 'Facture 2025002');
        $ready = $this->duplicate('2025002', InvoiceStatusEnum::Ready, 'Facture d\'inscriptions 2025002');

        $this->artisan('invoices:fix-duplicate-numbers --apply')->assertSuccessful();

        $this->assertSame('2025002', $sent->fresh()->number, 'la facture envoyée garde son numéro');
        $ready = $ready->fresh();
        $this->assertNotSame('2025002', $ready->number);
        $this->assertSame('Facture d\'inscriptions '.$ready->number, $ready->getRawOriginal('title'));
        $this->assertStringContainsString($ready->number, $ready->qr_reference);
        $this->assertSame(2, Invoice::distinct('number')->count('number'));
    }

    public function test_repair_command_never_renumbers_sent_invoices_and_reports_them(): void
    {
        $this->duplicate('2025003', InvoiceStatusEnum::Sent, 'Facture 2025003');
        $paid = $this->duplicate('2025003', InvoiceStatusEnum::Paid, 'Facture 2025003');

        $this->artisan('invoices:fix-duplicate-numbers --apply')
            ->expectsOutputToContain('À CORRIGER À LA MAIN')
            ->assertFailed();

        $this->assertSame('2025003', $paid->fresh()->number);
    }

    public function test_repair_command_reports_when_everything_is_fine(): void
    {
        $this->artisan('invoices:fix-duplicate-numbers')
            ->expectsOutputToContain('Aucun numéro de facture en double')
            ->assertSuccessful();
    }
}
