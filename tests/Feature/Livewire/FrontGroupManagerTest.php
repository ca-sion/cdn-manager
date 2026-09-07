<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use App\Livewire\FrontGroupManager;
use App\Models\RunRegistrationElement;
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
    public function it_can_switch_tabs_and_filter_conformity()
    {
        // 1. Conforming class (8 students with 3 girls)
        $regSchoolConform = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::School,
            'school_name'           => 'St-Guérin',
            'school_class_level'    => '4H',
        ]);
        for ($i = 0; $i < 3; $i++) {
            RunRegistrationElement::create([
                'run_registration_id' => $regSchoolConform->id,
                'first_name'          => "Fille $i",
                'last_name'           => 'Test',
                'gender'              => 'F',
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            RunRegistrationElement::create([
                'run_registration_id' => $regSchoolConform->id,
                'first_name'          => "Garçon $i",
                'last_name'           => 'Test',
                'gender'              => 'M',
            ]);
        }

        // 2. Incomplete class (2 students)
        $regSchoolIncomplete = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::School,
            'school_name'           => 'Champsec',
            'school_class_level'    => '3H',
        ]);
        RunRegistrationElement::create([
            'run_registration_id' => $regSchoolIncomplete->id,
            'first_name'          => 'Lucas',
            'last_name'           => 'Test',
            'gender'              => 'M',
        ]);

        $this->assertTrue($regSchoolConform->isSchoolTeamConform());
        $this->assertFalse($regSchoolIncomplete->isSchoolTeamConform());

        Livewire::test(FrontGroupManager::class)
            ->set('activeTab', 'school')
            ->set('conformityFilter', 'conform')
            ->assertSee('St-Guérin')
            ->assertDontSee('Champsec')
            ->set('conformityFilter', 'non_conform')
            ->assertSee('Champsec')
            ->assertDontSee('St-Guérin');
    }
}
