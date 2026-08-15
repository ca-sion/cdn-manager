<?php

namespace Tests\Unit\Models;

use App\Models\Run;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'min_age',
            'max_age',
            'datasport_code',
            'code',
            'accepts_voucher',
            'provision_id',
        ];

        $run = new Run;

        $this->assertEquals($fillable, $run->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $run = new Run;
        $casts = $run->getCasts();

        $this->assertEquals('array', $casts['available_for_types']);
        $this->assertEquals('array', $casts['start_blocs']);
        $this->assertEquals('datetime', $casts['registrations_deadline']);
        $this->assertEquals('boolean', $casts['accepts_voucher']);
        $this->assertEquals('integer', $casts['registrations_limit']);
        $this->assertEquals('integer', $casts['registrations_number']);
        $this->assertEquals('integer', $casts['min_age']);
        $this->assertEquals('integer', $casts['max_age']);
        $this->assertEquals('decimal:2', $casts['cost']);
        $this->assertEquals('decimal:2', $casts['distance']);
    }

    /** @test */
    public function it_verifies_age_restrictions_correctly()
    {
        $run = new Run(['min_age' => 18, 'max_age' => 35]);

        $this->assertTrue($run->matchesAge(18));
        $this->assertTrue($run->matchesAge(25));
        $this->assertTrue($run->matchesAge(35));
        $this->assertFalse($run->matchesAge(17));
        $this->assertFalse($run->matchesAge(36));
        $this->assertTrue($run->matchesAge(null));
    }

    /** @test */
    public function it_generates_correct_age_range_label()
    {
        $run1 = new Run(['min_age' => 18, 'max_age' => 99]);
        $this->assertEquals('18 à 99 ans', $run1->age_range_label);

        $run2 = new Run(['min_age' => 16, 'max_age' => null]);
        $this->assertEquals('dès 16 ans', $run2->age_range_label);

        $run3 = new Run(['min_age' => null, 'max_age' => 12]);
        $this->assertEquals("jusqu'à 12 ans", $run3->age_range_label);

        $run4 = new Run(['min_age' => null, 'max_age' => null]);
        $this->assertNull($run4->age_range_label);
    }
}
