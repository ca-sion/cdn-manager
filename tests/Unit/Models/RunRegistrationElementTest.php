<?php

namespace Tests\Unit\Models;

use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Models\Run;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunRegistrationElementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'run_registration_id',
            'first_name',
            'last_name',
            'birthdate',
            'gender',
            'nationality',
            'email',
            'team',
            'run_id',
            'run_name',
            'bloc',
            'with_video',
            'voucher_code',
            'address',
            'address_extension',
            'postal_code',
            'locality',
            'country',
            'iban',
            'payment_note',
            'has_free_registration_fee',
            'has_bonus_start',
            'bonus_start_amount',
            'bonus_ranking_amount',
            'bonus_arrival_amount',
            'has_accommodation',
            'accommodation_friday',
            'accommodation_saturday',
            'accommodation_precision',
            'has_expense_reimbursement',
            'expense_reimbursement_precision',
        ];

        $element = new RunRegistrationElement();
        $actual = $element->getFillable();
        sort($fillable);
        sort($actual);

        $this->assertEquals($fillable, $actual);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $element = new RunRegistrationElement();
        $casts = $element->getCasts();

        $this->assertEquals('date', $casts['birthdate']);
        $this->assertEquals('boolean', $casts['with_video']);
        $this->assertEquals('boolean', $casts['has_free_registration_fee']);
        $this->assertEquals('boolean', $casts['has_bonus_start']);
        $this->assertEquals('boolean', $casts['has_accommodation']);
        $this->assertEquals('boolean', $casts['accommodation_friday']);
        $this->assertEquals('boolean', $casts['accommodation_saturday']);
        $this->assertEquals('boolean', $casts['has_expense_reimbursement']);
    }

    /** @test */
    public function it_belongs_to_a_registration()
    {
        $registration = RunRegistration::factory()->create();
        $element = RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id]);

        $this->assertInstanceOf(RunRegistration::class, $element->runRegistration);
        $this->assertEquals($registration->id, $element->run_registration_id);
    }

    /** @test */
    public function it_belongs_to_a_run()
    {
        $run = Run::factory()->create();
        $element = RunRegistrationElement::factory()->create(['run_id' => $run->id]);

        $this->assertInstanceOf(Run::class, $element->run);
        $this->assertEquals($run->id, $element->run_id);
    }
}
