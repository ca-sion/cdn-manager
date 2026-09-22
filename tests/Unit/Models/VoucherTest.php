<?php

namespace Tests\Unit\Models;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Voucher;
use App\Models\Provision;
use App\Models\ProvisionElement;
use App\Notifications\ClientSendVouchers;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_voucher_and_verify_initial_state()
    {
        $voucher = Voucher::create([
            'code'    => 'CDN2026-TEST',
            'is_used' => false,
            'used_at' => null,
        ]);

        $this->assertDatabaseHas('vouchers', [
            'code'    => 'CDN2026-TEST',
            'is_used' => false,
        ]);

        $this->assertFalse($voucher->is_used);
        $this->assertNull($voucher->used_at);
    }

    /** @test */
    public function it_belongs_to_a_client_and_a_run_optionally()
    {
        Edition::factory()->create(['year' => (int) date('Y')]);

        $client = Client::factory()->create(['name' => 'UBS Sion']);
        $run = Run::factory()->create(['name' => 'Course Entreprises']);

        $voucher = Voucher::create([
            'code'      => 'CDN2026-UBS',
            'client_id' => $client->id,
            'run_id'    => $run->id,
            'is_used'   => false,
        ]);

        $this->assertEquals('UBS Sion', $voucher->client->name);
        $this->assertEquals('Course Entreprises', $voucher->run->name);
    }

    /** @test */
    public function it_can_mark_voucher_as_used()
    {
        $voucher = Voucher::create([
            'code'    => 'CDN2026-MARK',
            'is_used' => false,
        ]);

        $voucher->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        $this->assertTrue($voucher->fresh()->is_used);
        $this->assertNotNull($voucher->fresh()->used_at);
    }

    /** @test */
    public function it_calculates_client_voucher_quotas_and_missing_counts()
    {
        $edition = Edition::factory()->create(['year' => (int) date('Y')]);

        $cdnProvision = Provision::create([
            'name'       => 'Inscription Entreprise CDN',
            'code'       => 'PROV-CDN-TEST',
            'is_active'  => true,
            'edition_id' => $edition->id,
        ]);

        $trailProvision = Provision::create([
            'name'       => 'Inscription Trail',
            'code'       => 'PROV-TRAIL-TEST',
            'is_active'  => true,
            'edition_id' => $edition->id,
        ]);

        setting([
            'edition_id'               => $edition->id,
            'voucher_cdn_provisions'   => [$cdnProvision->id],
            'voucher_trail_provisions' => [$trailProvision->id],
        ]);

        $client = Client::factory()->create(['name' => 'Entreprise Valais SA']);

        ProvisionElement::create([
            'edition_id'        => $edition->id,
            'provision_id'      => $cdnProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $client->id,
            'numeric_indicator' => 5,
        ]);

        ProvisionElement::create([
            'edition_id'        => $edition->id,
            'provision_id'      => $trailProvision->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $client->id,
            'numeric_indicator' => 2,
        ]);

        $this->assertEquals(5, $client->cdn_vouchers_quota);
        $this->assertEquals(2, $client->trail_vouchers_quota);
        $this->assertEquals(0, $client->assigned_vouchers_count);
        $this->assertEquals(5, $client->missing_vouchers_count);

        Voucher::create([
            'code'       => 'CDN-AUTO-01',
            'client_id'  => $client->id,
            'edition_id' => $edition->id,
            'is_used'    => false,
        ]);

        $this->assertEquals(1, $client->fresh()->assigned_vouchers_count);
        $this->assertEquals(4, $client->fresh()->missing_vouchers_count);
    }

    /** @test */
    public function it_builds_client_send_vouchers_notification_correctly()
    {
        $edition = Edition::factory()->create(['year' => (int) date('Y')]);
        $client = Client::factory()->create(['name' => 'BCVs']);

        $vouchers = collect([
            Voucher::create([
                'code'       => 'BCVS-001',
                'client_id'  => $client->id,
                'edition_id' => $edition->id,
                'is_used'    => false,
            ]),
        ]);

        $notification = new ClientSendVouchers($vouchers, null, 2);
        $mail = $notification->toMail($client);

        $this->assertStringContainsString('BCVs', $mail->subject);
        $this->assertStringContainsString('inscriptions@traildeschateaux.ch', (string) $mail->render());
        $this->assertStringContainsString('challenge-entreprises', (string) $mail->render());
    }
}
