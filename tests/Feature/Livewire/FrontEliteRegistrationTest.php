<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Livewire\FrontEliteRegistration;
use App\Enums\RunRegistrationType;
use App\Enums\Gender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

class FrontEliteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_403_forbidden_when_accessing_elite_registration_without_valid_signature()
    {
        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Elite,
            'contact_first_name'    => 'Julien',
            'contact_last_name'     => 'Wanders',
        ]);

        $unsignedUrl = route('front.run-registration.elite-edit', ['registration' => $reg->id]);

        $response = $this->get($unsignedUrl);
        $response->assertStatus(403);
    }

    /** @test */
    public function it_loads_and_updates_elite_runner_details_with_valid_signature()
    {
        $runElite = Run::factory()->create(['name' => 'Course Élite']);

        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Elite,
            'contact_first_name'    => 'Tadesse',
            'contact_last_name'     => 'Abraham',
            'contact_email'         => 'tadesse@running.ch',
        ]);

        $element = RunRegistrationElement::create([
            'run_registration_id' => $reg->id,
            'first_name'          => 'Tadesse',
            'last_name'           => 'Abraham',
            'birthdate'           => '1982-08-12',
            'gender'              => Gender::Male,
            'nationality'         => 'SUI',
            'run_id'              => $runElite->id,
        ]);

        $signedUrl = URL::signedRoute('front.run-registration.elite-edit', ['registration' => $reg->id]);

        $response = $this->get($signedUrl);
        $response->assertStatus(200);

        Livewire::test(FrontEliteRegistration::class, ['registration' => $reg])
            ->set('data.elite_first_name', 'Tadesse')
            ->set('data.elite_last_name', 'Abraham')
            ->set('data.elite_team', 'Club Sion')
            ->set('data.elite_email', 'tadesse@running.ch')
            ->set('data.payment_iban', 'CH93 0000 0000 0000 1234 A')
            ->call('save')
            ->assertHasNoErrors();
    }
}
