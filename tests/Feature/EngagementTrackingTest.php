<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Enums\InvoiceStatusEnum;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Services\InvoiceService;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use App\Services\EngagementSnapshot;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\ListClients;

class EngagementTrackingTest extends TestCase
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
        $this->actingAs(User::factory()->create());
    }

    private function engagement(Client $client, ?EngagementStageEnum $stage = null, ?EngagementStatusEnum $status = null): ClientEngagement
    {
        return ClientEngagement::updateOrCreate(
            ['client_id' => $client->id, 'edition_id' => $this->edition->id],
            ['stage' => $stage, 'status' => $status],
        );
    }

    private function current(Client $client): ?ClientEngagement
    {
        return ClientEngagement::withoutGlobalScopes()->where('client_id', $client->id)->where('edition_id', $this->edition->id)->first();
    }

    private function invoice(Client $client, InvoiceStatusEnum $status, array $extra = []): Invoice
    {
        return Invoice::create(array_merge([
            'edition_id' => $this->edition->id, 'client_id' => $client->id, 'status' => $status,
            'number'     => 'T'.fake()->unique()->numerify('####'), 'positions' => [],
        ], $extra));
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

    // ───────────── Point 2 : envoi d'une facture ─────────────

    public function test_sending_an_invoice_moves_the_client_to_billed_and_waiting(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Confirmed, EngagementStatusEnum::ToRelaunch);
        $invoice = $this->invoice($client, InvoiceStatusEnum::Ready);

        $invoice->update(['status' => InvoiceStatusEnum::Sent]);

        $engagement = $this->current($client);
        $this->assertSame(EngagementStageEnum::Billed, $engagement->stage);
        $this->assertSame(EngagementStatusEnum::Idle, $engagement->status);
    }

    public function test_sending_a_new_invoice_takes_a_paid_client_back_to_billed(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Paid, null);
        $invoice = $this->invoice($client, InvoiceStatusEnum::Ready);

        $invoice->update(['status' => InvoiceStatusEnum::Sent]);

        $this->assertSame(EngagementStageEnum::Billed, $this->current($client)->stage);
    }

    // ───────────── Point 2 : « tout payé » ─────────────

    public function test_cancelled_and_pro_forma_invoices_do_not_block_the_paid_stage(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Billed, EngagementStatusEnum::Idle);
        $this->invoice($client, InvoiceStatusEnum::Cancelled);
        $this->invoice($client, InvoiceStatusEnum::Ready, ['is_pro_forma' => true]);
        $legacyPaid = $this->invoice($client, InvoiceStatusEnum::Payed);
        $invoice = $this->invoice($client, InvoiceStatusEnum::Sent);

        $invoice->update(['status' => InvoiceStatusEnum::Paid]);

        $this->assertSame(EngagementStageEnum::Paid, $this->current($client)->stage);
        $this->assertNotNull($legacyPaid);
    }

    public function test_a_real_unpaid_invoice_still_blocks_the_paid_stage(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Billed, EngagementStatusEnum::Idle);
        $this->invoice($client, InvoiceStatusEnum::Sent);
        $invoice = $this->invoice($client, InvoiceStatusEnum::Sent);

        $invoice->update(['status' => InvoiceStatusEnum::Paid]);

        $this->assertSame(EngagementStageEnum::Billed, $this->current($client)->stage);
    }

    // ───────────── Point 1 : annulation manuelle ─────────────

    public function test_cancelling_one_invoice_leaves_the_follow_up_alone_when_another_is_active(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Billed, EngagementStatusEnum::Idle);
        $this->invoice($client, InvoiceStatusEnum::Sent);
        $other = $this->invoice($client, InvoiceStatusEnum::Ready);

        $other->update(['status' => InvoiceStatusEnum::Cancelled]);

        $this->assertNotSame(EngagementStatusEnum::Cancelled, $this->current($client)->status, 'le suivi n\'est pas marqué Annulé');
    }

    public function test_cancelling_the_only_active_invoice_marks_the_client_cancelled(): void
    {
        $client = Client::factory()->create();
        $this->engagement($client, EngagementStageEnum::Billed, EngagementStatusEnum::Idle);
        $this->invoice($client, InvoiceStatusEnum::Cancelled);
        $this->invoice($client, InvoiceStatusEnum::Ready, ['is_pro_forma' => true]);
        $only = $this->invoice($client, InvoiceStatusEnum::Ready);

        $only->update(['status' => InvoiceStatusEnum::Cancelled]);

        $this->assertSame(EngagementStatusEnum::Cancelled, $this->current($client)->status);
    }

    // ───────────── Point 1 : annuler un bulk ─────────────

    public function test_undoing_a_bulk_restores_the_follow_up_as_it_was(): void
    {
        $withFollowUp = $this->clientWithProvision();
        $this->engagement($withFollowUp, EngagementStageEnum::Confirmed, EngagementStatusEnum::Idle);
        $withoutFollowUp = $this->clientWithProvision();

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$withFollowUp, $withoutFollowUp], data: [
                'provision_client_ids' => [$withFollowUp->id, $withoutFollowUp->id],
            ]);

        // Le bulk a mis les deux clients en « Facturé / À modifier »
        $this->assertSame(EngagementStageEnum::Billed, $this->current($withFollowUp)->stage);
        $this->assertNotNull($this->current($withoutFollowUp));

        $notification = $this->lastNotification();
        $data = $notification['actions'][0]['eventData'];

        Livewire::test(ListClients::class)->call('undoBulkInvoices', $data['invoiceIds'], $data['engagements']);

        $restored = $this->current($withFollowUp);
        $this->assertSame(EngagementStageEnum::Confirmed, $restored->stage, 'étape d\'avant restaurée');
        $this->assertSame(EngagementStatusEnum::Idle, $restored->status, 'statut d\'avant restauré');
        $untouched = $this->current($withoutFollowUp);
        $this->assertSame(EngagementStageEnum::Prospect, $untouched->stage, 'un client « Prospect » redevient Prospect');
        $this->assertNull($untouched->status);
        $this->assertSame(2, Invoice::where('status', InvoiceStatusEnum::Cancelled->value)->count());
    }

    public function test_undo_does_not_touch_a_follow_up_changed_in_the_meantime(): void
    {
        $client = $this->clientWithProvision();
        $this->engagement($client, EngagementStageEnum::Confirmed, EngagementStatusEnum::Idle);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$client], data: ['provision_client_ids' => [$client->id]]);
        $data = $this->lastNotification()['actions'][0]['eventData'];

        // Quelqu'un a mis le client en « Perdu » après le bulk
        $this->engagement($client, EngagementStageEnum::Lost, EngagementStatusEnum::Idle);

        Livewire::test(ListClients::class)->call('undoBulkInvoices', $data['invoiceIds'], $data['engagements']);

        $this->assertSame(EngagementStageEnum::Lost, $this->current($client)->stage, 'la modification manuelle est respectée');
        $this->assertSame(InvoiceStatusEnum::Cancelled, Invoice::firstOrFail()->status, 'la facture est quand même annulée');
    }

    public function test_undo_does_not_restore_when_one_of_the_clients_invoices_was_already_sent(): void
    {
        $client = $this->clientWithProvision();
        $this->engagement($client, EngagementStageEnum::Confirmed, EngagementStatusEnum::Idle);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$client], data: ['provision_client_ids' => [$client->id]]);
        $data = $this->lastNotification()['actions'][0]['eventData'];
        Invoice::firstOrFail()->update(['status' => InvoiceStatusEnum::Sent]);

        Livewire::test(ListClients::class)->call('undoBulkInvoices', $data['invoiceIds'], $data['engagements']);

        $this->assertSame(InvoiceStatusEnum::Sent, Invoice::firstOrFail()->status, 'facture envoyée jamais annulée');
        $this->assertSame(EngagementStageEnum::Billed, $this->current($client)->stage, 'suivi cohérent avec la facture envoyée');
    }

    public function test_undo_without_snapshot_still_works_like_a_manual_cancellation(): void
    {
        $client = $this->clientWithProvision();
        $invoice = InvoiceService::generateInvoiceByClient($client->id);

        $result = InvoiceService::cancelGeneratedInvoices([$invoice->id]);

        $this->assertSame(['cancelled' => 1, 'skipped' => 0], $result);
        $this->assertSame(EngagementStatusEnum::Cancelled, $this->current($client)->status);
    }

    public function test_restore_deletes_an_empty_follow_up_that_did_not_exist_before_and_keeps_one_with_data(): void
    {
        $empty = Client::factory()->create();
        $withData = Client::factory()->create();
        ClientEngagement::withoutGlobalScopes()->whereIn('client_id', [$empty->id, $withData->id])->delete();
        $this->engagement($empty, EngagementStageEnum::Billed, EngagementStatusEnum::ToModify);
        $this->engagement($withData, EngagementStageEnum::Billed, EngagementStatusEnum::ToModify)->update(['note' => 'à garder']);

        $snapshot = ['before' => ['existed' => false, 'stage' => null, 'status' => null], 'after' => ['existed' => true, 'stage' => 'billed', 'status' => 'to_modify']];
        EngagementSnapshot::restore([$empty->id => $snapshot, $withData->id => $snapshot], [$empty->id, $withData->id]);

        $this->assertNull($this->current($empty), 'suivi vide créé par le bulk : supprimé');
        $kept = $this->current($withData);
        $this->assertNull($kept->stage);
        $this->assertSame('à garder', $kept->note, 'une note saisie à la main n\'est jamais perdue');
    }

    private function lastNotification(): array
    {
        $component = new Notifications;
        $component->mount();

        return $component->notifications->first()->toArray();
    }
}
