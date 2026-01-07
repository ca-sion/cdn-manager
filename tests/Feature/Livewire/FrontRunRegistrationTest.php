<?php

namespace Tests\Feature\Livewire;

use App\Livewire\FrontRunRegistration;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontRunRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_the_registration_form_for_companies()
    {
        Livewire::test(FrontRunRegistration::class, ['type' => 'company'])
            ->assertStatus(200);
    }

    /** @test */
    public function it_renders_the_registration_form_for_schools()
    {
        Livewire::test(FrontRunRegistration::class, ['type' => 'school'])
            ->assertStatus(200);
    }
}
