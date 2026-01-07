<?php

namespace Tests\Unit\Models;

use App\Models\RunRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'client_id',
            'type',
            'invoicing_company_name',
            'invoicing_address',
            'invoicing_address_extension',
            'invoicing_postal_code',
            'invoicing_locality',
            'invoicing_email',
            'invoicing_note',
            'payment_iban',
            'payment_note',
            'company_name',
            'school_name',
            'school_postal_code',
            'school_locality',
            'school_country',
            'school_class_level',
            'school_class_holder_first_name',
            'school_class_holder_last_name',
            'school_class_holder_email',
            'school_class_holder_phone',
            'contact_first_name',
            'contact_last_name',
            'contact_email',
            'contact_phone',
        ];

        $runRegistration = new RunRegistration();

        // sort arrays to ensure order doesn't matter
        $expected = $fillable;
        $actual = $runRegistration->getFillable();
        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $runRegistration = new RunRegistration();
        $casts = $runRegistration->getCasts();

        // Enums and other casts
        // $this->assertEquals(RunRegistrationTypesEnum::class, $casts['type']); // We'll test this after implementing Enum
        $this->assertTrue(in_array('deleted_at', array_keys($casts)) || in_array('deleted_at', $runRegistration->getDates()));
    }
    
    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(RunRegistration::class)));
    }
}
