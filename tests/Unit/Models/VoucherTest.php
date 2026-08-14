<?php

namespace Tests\Unit\Models;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Voucher;
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
    public function it_can_filter_valid_unclaimed_vouchers()
    {
        Voucher::create(['code' => 'USED1', 'is_used' => true, 'used_at' => now()]);
        Voucher::create(['code' => 'FREE1', 'is_used' => false]);
        Voucher::create(['code' => 'FREE2', 'is_used' => false]);

        $available = Voucher::where('is_used', false)->get();

        $this->assertCount(2, $available);
        $this->assertContains('FREE1', $available->pluck('code'));
        $this->assertContains('FREE2', $available->pluck('code'));
    }
}
