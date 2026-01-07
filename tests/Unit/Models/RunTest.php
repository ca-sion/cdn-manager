<?php

namespace Tests\Unit\Models;

use App\Models\Run;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'name',
            'distance',
            'cost',
            'available_for_types',
            'start_blocs',
            'registrations_deadline',
            'registrations_limit',
            'registrations_number',
            'datasport_code',
            'code',
            'accepts_voucher',
            'provision_id',
        ];

        $run = new Run();

        $this->assertEquals($fillable, $run->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $run = new Run();
        $casts = $run->getCasts();

        $this->assertEquals('array', $casts['available_for_types']);
        $this->assertEquals('array', $casts['start_blocs']);
        $this->assertEquals('date', $casts['registrations_deadline']);
        $this->assertEquals('boolean', $casts['accepts_voucher']);
        $this->assertEquals('integer', $casts['registrations_limit']);
        $this->assertEquals('integer', $casts['registrations_number']);
        $this->assertEquals('decimal:2', $casts['cost']);
        $this->assertEquals('decimal:2', $casts['distance']);
    }
}
