<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use App\Notifications\ClientSendInvoice;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ClientSendInvoiceRelaunch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;

class InvoiceActionsTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $edition->id]);
        config(['cdn.default_edition_id' => $edition->id]);

        $this->actingAs(User::factory()->create());

        $this->client = Client::factory()->create();
        $this->invoice = Invoice::create([
            'edition_id' => $edition->id,
            'client_id'  => $this->client->id,
            'status'     => InvoiceStatusEnum::Ready,
            'number'     => '2025-001',
            'date'       => now(),
        ]);
    }

    public function test_invoice_list_send_and_relaunch_actions_work(): void
    {
        Notification::fake();

        Livewire::test(ListInvoices::class)
            ->assertCanSeeTableRecords([$this->invoice])
            ->callTableAction('sendInvoice', $this->invoice);

        Notification::assertSentTo($this->client, ClientSendInvoice::class);
        $this->assertEquals(InvoiceStatusEnum::Sent, $this->invoice->fresh()->status);

        Livewire::test(ListInvoices::class)
            ->callTableAction('relaunchInvoice', $this->invoice);

        Notification::assertSentTo($this->client, ClientSendInvoiceRelaunch::class);
        $this->assertEquals(InvoiceStatusEnum::Relaunched, $this->invoice->fresh()->status);
    }

    public function test_client_invoices_relation_manager_has_the_full_invoice_actions(): void
    {
        Notification::fake();

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $this->client,
            'pageClass'   => EditClient::class,
        ])
            ->assertCanSeeTableRecords([$this->invoice])
            ->assertTableActionExists('relaunchInvoice')
            ->assertTableActionExists('resetInvoiceViewedAt')
            ->assertTableActionExists('downloadInvoiceEml')
            ->callTableAction('sendInvoice', $this->invoice);

        Notification::assertSentTo($this->client, ClientSendInvoice::class);
    }

    public function test_client_list_can_send_the_latest_invoice_of_a_client(): void
    {
        Notification::fake();

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$this->client])
            ->callTableAction('sendInvoice', $this->client);

        Notification::assertSentTo($this->client, ClientSendInvoice::class);
    }

    public function test_provision_element_row_can_open_invoice_actions_for_its_client(): void
    {
        Notification::fake();

        $element = ProvisionElement::factory()->create([
            'provision_id'   => Provision::factory()->create(['edition_id' => $this->invoice->edition_id, 'product_id' => null])->id,
            'edition_id'     => $this->invoice->edition_id,
            'recipient_id'   => $this->client->id,
            'recipient_type' => Client::class,
        ]);

        Livewire::test(ListProvisionElements::class)
            ->assertCanSeeTableRecords([$element])
            ->callTableAction('sendInvoice', $element);

        Notification::assertSentTo($this->client, ClientSendInvoice::class);
    }
}
