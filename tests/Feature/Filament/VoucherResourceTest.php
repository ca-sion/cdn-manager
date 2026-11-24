<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Voucher;
use App\Models\Provision;
use App\Models\ProvisionElement;
use Filament\Actions\Testing\TestAction;
use App\Notifications\ClientSendVouchers;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\VoucherResource\Pages\ListVouchers;

class VoucherResourceTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private Provision $cdnProvision;

    private Provision $trailProvision;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);

        $this->cdnProvision = Provision::factory()->create(['product_id' => null]);
        $this->trailProvision = Provision::factory()->create(['product_id' => null]);

        setting([
            'voucher_cdn_provisions'   => [(string) $this->cdnProvision->id],
            'voucher_trail_provisions' => [(string) $this->trailProvision->id],
        ]);

        $this->actingAs(User::factory()->create());
    }

    public function test_vouchers_page_exposes_clean_header_and_bulk_actions(): void
    {
        Livewire::test(ListVouchers::class)
            ->assertTableActionExists('sendVouchers')
            ->assertTableActionExists('importAndDispatchVouchers')
            ->assertTableActionExists('exportVouchersOverview')
            ->assertTableActionDoesNotExist('autoDispatchVouchers')
            ->assertTableActionDoesNotExist('importVouchers')
            ->assertTableActionDoesNotExist('sendVouchersEmail')
            ->assertTableActionDoesNotExist('sendAllVouchersEmails')
            ->assertTableBulkActionExists('sendVouchers')
            ->assertTableBulkActionExists('assignClient');
    }

    public function test_header_send_vouchers_preselects_ready_and_trail_clients_skips_incomplete_and_no_email(): void
    {
        Notification::fake();

        $ready = Client::factory()->create(['email' => 'ready@example.test', 'name' => 'Alpha']);
        Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $ready->id, 'code' => 'CDN-READY-1']);

        $trailOnly = Client::factory()->create(['email' => 'trail@example.test', 'name' => 'Beta']);
        ProvisionElement::create([
            'edition_id'        => $this->edition->id,
            'provision_id'      => $this->trailProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $trailOnly->id,
            'numeric_indicator' => '3',
        ]);

        $incomplete = Client::factory()->create(['email' => 'incomplete@example.test', 'name' => 'Gamma']);
        ProvisionElement::create([
            'edition_id'        => $this->edition->id,
            'provision_id'      => $this->cdnProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $incomplete->id,
            'numeric_indicator' => '2',
        ]);
        Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $incomplete->id, 'code' => 'CDN-INC-1']);

        $noEmail = Client::factory()->create(['email' => null, 'name' => 'Delta']);
        Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $noEmail->id, 'code' => 'CDN-NOEMAIL-1']);

        Livewire::test(ListVouchers::class)
            ->mountAction(TestAction::make('sendVouchers')->table())
            ->assertSchemaStateSet([
                'client_ids' => [$ready->id, $trailOnly->id],
            ])
            ->callMountedAction();

        Notification::assertSentTo($ready, ClientSendVouchers::class);
        Notification::assertSentTo($trailOnly, ClientSendVouchers::class);
        Notification::assertNotSentTo($incomplete, ClientSendVouchers::class);
        Notification::assertNotSentTo($noEmail, ClientSendVouchers::class);
    }

    public function test_bulk_send_vouchers_on_vouchers_table_resolves_clients_and_ignores_unassigned(): void
    {
        Notification::fake();

        $client = Client::factory()->create(['email' => 'client@example.test']);
        $v1 = Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $client->id, 'code' => 'V-1']);
        $v2 = Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $client->id, 'code' => 'V-2']);
        $unassigned = Voucher::create(['edition_id' => $this->edition->id, 'client_id' => null, 'code' => 'V-FREE']);

        Livewire::test(ListVouchers::class)
            ->selectTableRecords([$v1->id, $v2->id, $unassigned->id])
            ->mountAction(TestAction::make('sendVouchers')->table()->bulk())
            ->assertSchemaStateSet([
                'client_ids' => [$client->id],
            ])
            ->callMountedAction();

        Notification::assertSentToTimes($client, ClientSendVouchers::class, 1);
    }

    public function test_bulk_assign_client_assigns_selected_vouchers(): void
    {
        $client = Client::factory()->create();
        $v1 = Voucher::create(['edition_id' => $this->edition->id, 'client_id' => null, 'code' => 'STOCK-1']);
        $v2 = Voucher::create(['edition_id' => $this->edition->id, 'client_id' => null, 'code' => 'STOCK-2']);

        Livewire::test(ListVouchers::class)
            ->selectTableRecords([$v1->id, $v2->id])
            ->mountAction(TestAction::make('assignClient')->table()->bulk())
            ->fillForm(['client_id' => $client->id])
            ->callMountedAction();

        $this->assertSame($client->id, $v1->fresh()->client_id);
        $this->assertSame($client->id, $v2->fresh()->client_id);
    }

    public function test_import_and_dispatch_supports_manual_stock_and_auto_dispatch(): void
    {
        $client = Client::factory()->create();
        ProvisionElement::create([
            'edition_id'        => $this->edition->id,
            'provision_id'      => $this->cdnProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $client->id,
            'numeric_indicator' => '2',
        ]);

        Livewire::test(ListVouchers::class)
            ->callTableAction('importAndDispatchVouchers', data: [
                'mode'       => 'manual',
                'client_id'  => null,
                'codes_text' => "FREE-1\nFREE-2",
            ]);

        $this->assertDatabaseHas('vouchers', ['code' => 'FREE-1', 'client_id' => null]);
        $this->assertDatabaseHas('vouchers', ['code' => 'FREE-2', 'client_id' => null]);

        Livewire::test(ListVouchers::class)
            ->callTableAction('importAndDispatchVouchers', data: [
                'mode'       => 'auto',
                'codes_text' => "AUTO-1\nAUTO-2\nAUTO-3",
            ]);

        $this->assertDatabaseHas('vouchers', ['code' => 'AUTO-1', 'client_id' => $client->id]);
        $this->assertDatabaseHas('vouchers', ['code' => 'AUTO-2', 'client_id' => $client->id]);
        $this->assertDatabaseMissing('vouchers', ['code' => 'AUTO-3']);
    }

    public function test_clients_table_exposes_shared_bulk_and_row_voucher_actions(): void
    {
        Notification::fake();

        $ready = Client::factory()->create(['email' => 'ready@example.test']);
        Voucher::create(['edition_id' => $this->edition->id, 'client_id' => $ready->id, 'code' => 'C-1']);

        $empty = Client::factory()->create(['email' => 'empty@example.test']);

        Livewire::test(ListClients::class)
            ->assertTableBulkActionExists('sendVouchers')
            ->selectTableRecords([$ready->id, $empty->id])
            ->mountAction(TestAction::make('sendVouchers')->table()->bulk())
            ->assertSchemaStateSet([
                'client_ids' => [$ready->id],
            ])
            ->callMountedAction();

        Notification::assertSentTo($ready, ClientSendVouchers::class);
        Notification::assertNotSentTo($empty, ClientSendVouchers::class);
    }
}
