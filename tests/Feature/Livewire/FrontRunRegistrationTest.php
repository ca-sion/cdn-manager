<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Livewire\FrontRunRegistration;
use App\Enums\RunRegistrationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

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

    /** @test */
    public function it_cleans_empty_rows_from_laragrid_elements()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school']);

        $comp->set('elements', [
            ['_k' => 'l1', 'first_name' => 'Jean', 'last_name' => 'Favre', 'birthdate' => '12.04.2014'],
            ['_k' => 'l2', 'first_name' => '', 'last_name' => '', 'birthdate' => ''],
            ['_k' => 'l3', 'first_name' => '   ', 'last_name' => '', 'birthdate' => ''],
        ]);

        $comp->call('cleanEmptyRows');

        $elements = $comp->get('elements');
        $this->assertCount(1, $elements);
        $this->assertEquals('Jean', $elements[0]['first_name']);
    }

    /** @test */
    public function it_flags_integrity_errors_when_row_data_is_invalid()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Marc',
                'last_name'  => 'Dubuis',
                'birthdate'  => '99.99.9999', // Invalid date
                'gender'     => 'X',          // Invalid gender
                'email'      => 'invalid-email',
                'run_id'     => '',
            ],
        ]);

        $comp->call('verifyIntegrity');

        $errors = $comp->get('integrityErrors');
        $this->assertNotEmpty($errors);
        $this->assertCount(1, $errors);
    }

    /** @test */
    public function it_allows_editing_via_signed_url()
    {
        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Company,
            'company_name'          => 'Nestlé',
            'contact_first_name'    => 'Pierre',
            'contact_last_name'     => 'Martin',
            'contact_email'         => 'pierre@nestle.com',
        ]);

        $signedUrl = URL::signedRoute('front.run-registration.edit', ['registration' => $reg->id]);

        $response = $this->get($signedUrl);
        $response->assertStatus(200);
    }
}
