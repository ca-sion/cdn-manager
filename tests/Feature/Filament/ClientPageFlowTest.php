<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use App\Services\InvoiceService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ClientPageFlowTest extends TestCase
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

        $this->actingAs(User::factory()->create());
        $this->client = Client::factory()->create();
    }

    private function element(?Client $client = null, ?Provision $provision = null): ProvisionElement
    {
        $client ??= $this->client;
        $provision ??= Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);

        return ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
            'provision_id' => $provision->id, 'has_product' => true, 'quantity' => 1, 'cost' => 100, 'tax_rate' => 8.1,
        ]);
    }

    public function test_edit_client_page_shows_grouped_actions(): void
    {
        Livewire::test(EditClient::class, ['record' => $this->client->getKey()])
            ->assertSuccessful()
            ->assertActionExists('clientPdf')
            ->assertActionExists('generateInvoice')
            ->assertActionExists('sendClientVouchers')
            ->assertActionExists('sendMediaMissing')
            ->assertActionExists('ClientAdvertiserFormCreated')
            ->assertActionExists('ModifyStageStatus');
    }

    public function test_generating_from_the_client_page_only_invoices_selected_provisions(): void
    {
        $a = $this->element();
        $b = $this->element();

        Livewire::test(EditClient::class, ['record' => $this->client->getKey()])
            ->callAction('generateInvoice', data: ['element_ids' => [$a->id]])
            ->assertHasNoActionErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame([$a->id], $invoice->provisionElementIds());
        $this->assertSame([$b->id], InvoiceService::uninvoicedProvisionElements($this->client->id)->pluck('id')->all());
    }

    public function test_generating_twice_does_not_create_a_second_invoice(): void
    {
        $this->element();

        $page = Livewire::test(EditClient::class, ['record' => $this->client->getKey()]);
        $page->callAction('generateInvoice', data: ['element_ids' => InvoiceService::uninvoicedProvisionElements($this->client->id)->pluck('id')->all()]);
        $page->callAction('generateInvoice', data: ['element_ids' => []]);

        $this->assertEquals(1, Invoice::count());
    }

    public function test_provision_relation_manager_has_resource_actions_and_header_generate(): void
    {
        $element = $this->element();

        Livewire::test(ProvisionElementsRelationManager::class, [
            'ownerRecord' => $this->client,
            'pageClass'   => EditClient::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$element])
            ->assertTableActionExists('replicate', record: $element)
            ->assertTableActionExists('frontEditLink', record: $element)
            ->assertTableActionExists('sendInvoice', record: $element)
            ->assertTableBulkActionExists('bulkEdit')
            ->assertTableBulkActionExists('sendMediaMissing')
            ->assertTableBulkActionExists('copyEmail')
            ->callAction(TestAction::make('generateInvoice')->table(), data: ['element_ids' => [$element->id]]);

        $this->assertSame([$element->id], Invoice::firstOrFail()->provisionElementIds());
    }

    public function test_invoice_state_column_reflects_billing(): void
    {
        $element = $this->element();

        $component = Livewire::test(ProvisionElementsRelationManager::class, [
            'ownerRecord' => $this->client,
            'pageClass'   => EditClient::class,
        ]);
        $component->assertTableColumnStateSet('invoice_state', 'À facturer', record: $element);

        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);

        Livewire::test(ProvisionElementsRelationManager::class, [
            'ownerRecord' => $this->client,
            'pageClass'   => EditClient::class,
        ])->assertTableColumnStateSet('invoice_state', 'Facturée '.$invoice->number, record: $element);
    }

    public function test_bulk_generation_skips_clients_without_new_provisions(): void
    {
        $other = Client::factory()->create();
        $this->element();
        $this->element($other);
        InvoiceService::generateInvoiceByClient($other->id);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$this->client, $other]);

        $this->assertEquals(1, Invoice::where('client_id', $this->client->id)->count());
        $this->assertEquals(1, Invoice::where('client_id', $other->id)->count());
    }

    public function test_client_filters_support_search_and_multiple_values(): void
    {
        $catA = ClientCategory::create(['name' => 'Banque']);
        $catB = ClientCategory::create(['name' => 'Commerce']);
        $catC = ClientCategory::create(['name' => 'Autre']);
        $a = Client::factory()->create(['category_id' => $catA->id]);
        $b = Client::factory()->create(['category_id' => $catB->id]);
        $c = Client::factory()->create(['category_id' => $catC->id]);

        Livewire::test(ListClients::class)
            ->filterTable('category', [$catA->id, $catB->id])
            ->assertCanSeeTableRecords([$a, $b])
            ->assertCanNotSeeTableRecords([$c]);
    }

    public function test_provision_filters_on_clients_keep_working(): void
    {
        $provision = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        $with = Client::factory()->create();
        $without = Client::factory()->create();
        $this->element($with, $provision);

        Livewire::test(ListClients::class)
            ->filterTable('provision_in', [$provision->id])
            ->assertCanSeeTableRecords([$with])
            ->assertCanNotSeeTableRecords([$without]);

        Livewire::test(ListClients::class)
            ->filterTable('provision_not_in', [$provision->id])
            ->assertCanSeeTableRecords([$without])
            ->assertCanNotSeeTableRecords([$with]);
    }

    public function test_provision_element_filters_accept_several_values(): void
    {
        $p1 = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        $p2 = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        $p3 = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        $e1 = $this->element(provision: $p1);
        $e2 = $this->element(provision: $p2);
        $e3 = $this->element(provision: $p3);

        Livewire::test(ListProvisionElements::class)
            ->filterTable('provision', [$p1->id, $p2->id])
            ->assertCanSeeTableRecords([$e1, $e2])
            ->assertCanNotSeeTableRecords([$e3]);
    }

    public function test_invoice_edit_page_keeps_provision_ids_when_saved(): void
    {
        $element = $this->element();
        $invoice = InvoiceService::generateInvoiceByClient($this->client->id);

        Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
            ->assertSuccessful()
            ->fillForm(['title' => 'Titre modifié'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$element->id], $invoice->fresh()->provisionElementIds());
    }

    public function test_invoice_list_actions_and_relation_manager_invoice_actions(): void
    {
        $invoice = Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => $this->client->id,
            'status'     => InvoiceStatusEnum::Ready, 'number' => '2025-001', 'date' => now(),
        ]);

        Livewire::test(ListInvoices::class)->assertCanSeeTableRecords([$invoice])->assertTableActionExists('invoicePdf', record: $invoice);
        Livewire::test(InvoicesRelationManager::class, ['ownerRecord' => $this->client, 'pageClass' => EditClient::class])
            ->assertCanSeeTableRecords([$invoice])
            ->assertTableActionExists('resetInvoiceViewedAt', record: $invoice);
    }

    public function test_modal_explains_when_everything_is_already_invoiced(): void
    {
        $element = $this->element();
        Invoice::create([
            'edition_id' => $this->edition->id, 'client_id' => $this->client->id, 'status' => InvoiceStatusEnum::Sent,
            'number'     => '2025001', 'positions' => [['name' => $element->name, 'quantity' => 1, 'cost' => 100, 'tax_rate' => 8.1]],
        ]);

        Livewire::test(EditClient::class, ['record' => $this->client->getKey()])
            ->mountAction('generateInvoice')
            ->assertMountedActionModalSee('0 à facturer')
            ->assertMountedActionModalSee('1 déjà facturée(s)')
            ->assertMountedActionModalSee('Rien à facturer')
            ->assertMountedActionModalSee('Facture 2025001')
            ->assertMountedActionModalSee($element->name)
            ->assertMountedActionModalDontSee('Prestations à mettre sur la facture');
    }

    public function test_modal_lists_provisions_to_invoice_and_what_is_already_invoiced(): void
    {
        $done = $this->element();
        $todo = $this->element();
        InvoiceService::generateInvoiceByClient($this->client->id, [$done->id]);

        Livewire::test(EditClient::class, ['record' => $this->client->getKey()])
            ->mountAction('generateInvoice')
            ->assertMountedActionModalSee('1 à facturer')
            ->assertMountedActionModalSee('1 déjà facturée(s)')
            ->assertMountedActionModalSee('Prestations à mettre sur la facture')
            ->assertMountedActionModalSee($todo->name)
            ->assertMountedActionModalSee('Une nouvelle facture');
    }

    public function test_modal_mentions_provisions_without_price(): void
    {
        $provision = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $this->client->id, 'recipient_type' => Client::class,
            'provision_id' => $provision->id, 'has_product' => false,
        ]);

        Livewire::test(EditClient::class, ['record' => $this->client->getKey()])
            ->mountAction('generateInvoice')
            ->assertMountedActionModalSee('1 sans prix')
            ->assertMountedActionModalSee('aucune prestation avec un prix');
    }
}
