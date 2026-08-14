<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use App\Livewire\FrontGroupManager;
use App\Models\RunRegistrationElement;
use App\Notifications\RunRegistrationLink;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontGroupManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_the_group_manager_dashboard()
    {
        $regCompany = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Company,
            'company_name'          => 'UBS',
        ]);
        RunRegistrationElement::create([
            'run_registration_id' => $regCompany->id,
            'first_name'          => 'Gilles',
            'last_name'           => 'Lausannois',
        ]);

        $regSchool = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::School,
            'school_name'           => 'Sacré-Cœur',
        ]);
        RunRegistrationElement::create([
            'run_registration_id' => $regSchool->id,
            'first_name'          => 'Lucas',
            'last_name'           => 'Bessel',
        ]);

        Livewire::test(FrontGroupManager::class)
            ->assertStatus(200);
    }

    /** @test */
    public function it_can_send_edit_link_notification_to_target_email()
    {
        Notification::fake();

        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Group,
            'contact_first_name'    => 'Antoine',
            'contact_last_name'     => 'Clivaz',
            'contact_email'         => 'antoine@clivaz.ch',
        ]);

        Livewire::test(FrontGroupManager::class)
            ->call('sendEditLink', $reg->id);

        Notification::assertSentTo($reg, RunRegistrationLink::class);
    }
}
