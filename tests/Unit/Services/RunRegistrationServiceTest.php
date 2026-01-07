<?php

namespace Tests\Unit\Services;

use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Services\RunRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RunRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RunRegistrationService();
    }

    /** @test */
    public function it_can_calculate_total_amount_of_a_registration()
    {
        $run1 = Run::factory()->create(['cost' => 20.00]);
        $run2 = Run::factory()->create(['cost' => 35.00]);

        $registration = RunRegistration::factory()->create();
        
        RunRegistrationElement::factory()->create([
            'run_registration_id' => $registration->id,
            'run_id' => $run1->id,
            'has_free_registration_fee' => false,
        ]);

        RunRegistrationElement::factory()->create([
            'run_registration_id' => $registration->id,
            'run_id' => $run2->id,
            'has_free_registration_fee' => false,
        ]);

        // Element gratuit ne doit pas compter
        RunRegistrationElement::factory()->create([
            'run_registration_id' => $registration->id,
            'run_id' => $run2->id,
            'has_free_registration_fee' => true,
        ]);

        $total = $this->service->calculateTotal($registration);

        $this->assertEquals(55.00, $total);
    }

    /** @test */
    public function it_can_verify_if_registration_is_still_open()
    {
        $run = Run::factory()->create([
            'registrations_deadline' => now()->addDays(2)
        ]);

        $this->assertTrue($this->service->isRegistrationOpen($run));

        $runPast = Run::factory()->create([
            'registrations_deadline' => now()->subDays(1)
        ]);

        $this->assertFalse($this->service->isRegistrationOpen($runPast));
    }
}
