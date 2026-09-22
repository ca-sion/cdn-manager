<?php

namespace Tests\Feature\Livewire;

use App\Models\Run;
use Tests\TestCase;
use App\Models\School;
use Livewire\Livewire;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use Illuminate\Support\Facades\URL;
use App\Livewire\FrontRunRegistration;
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

    /** @test */
    public function it_flags_age_restriction_errors_in_verify_integrity()
    {
        $run18 = Run::create([
            'name'                => 'Trail Adulte 20km',
            'min_age'             => 18,
            'available_for_types' => ['group'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Lucas',
                'last_name'  => 'Rey',
                'birthdate'  => '15.08.2012', // 14 years old
                'gender'     => 'M',
                'email'      => 'lucas@example.com',
                'run_id'     => (string) $run18->id,
            ],
        ]);

        $comp->call('verifyIntegrity');

        $errors = $comp->get('integrityErrors');
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_flags_age_restriction_errors_for_company_registrations()
    {
        $runCompany = Run::create([
            'name'                => 'Challenge Entreprises',
            'min_age'             => 16,
            'available_for_types' => ['company'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'company']);

        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Bébé',
                'last_name'  => 'Test',
                'birthdate'  => '02.02.2026', // 0 years old
                'gender'     => 'M',
                'email'      => 'bebe@example.com',
            ],
        ]);

        $comp->call('verifyIntegrity');

        $errors = $comp->get('integrityErrors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Âge insuffisant', $errors[0]['errors'][0]);
    }

    /** @test */
    public function it_parses_pasted_excel_text_and_populates_elements()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $pasted = "Lucas\tRey\t02.02.2018\tM\tlucas@example.com\tSUI\nEmma\tDubois\t15.08.2008\tF\temma@example.com\tSUI";
        $comp->set('pasteTextData', $pasted);
        $comp->call('processPasteText');

        $elements = $comp->get('elements');
        $this->assertCount(2, $elements);
        $this->assertEquals('Lucas', $elements[0]['first_name']);
        $this->assertEquals('02.02.2018', $elements[0]['birthdate']);
        $this->assertEquals('Emma', $elements[1]['first_name']);
        $this->assertEquals('15.08.2008', $elements[1]['birthdate']);
    }

    /** @test */
    public function it_filters_available_runs_by_birthdate_age_in_reactive_table()
    {
        $childRun = Run::create([
            'name'                => 'Course Enfants 1',
            'min_age'             => 5,
            'max_age'             => 9,
            'available_for_types' => ['group'],
        ]);

        $adultRun = Run::create([
            'name'                => 'Trail Adulte 50km',
            'min_age'             => 18,
            'max_age'             => null,
            'available_for_types' => ['group'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        // For a 2018 birthdate (8 years old)
        $childRuns = $comp->instance()->getRunsForBirthdate('02.02.2018');
        $this->assertArrayHasKey((string) $childRun->id, $childRuns);
        $this->assertArrayNotHasKey((string) $adultRun->id, $childRuns);

        // For a 2008 birthdate (18 years old)
        $adultRuns = $comp->instance()->getRunsForBirthdate('02.02.2008');
        $this->assertArrayHasKey((string) $adultRun->id, $adultRuns);
        $this->assertArrayNotHasKey((string) $childRun->id, $adultRuns);
    }

    /** @test */
    public function it_resets_incompatible_run_id_when_birthdate_is_updated()
    {
        $adultRun = Run::create([
            'name'                => 'Trail Adulte 20km',
            'min_age'             => 18,
            'available_for_types' => ['group'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Marc',
                'last_name'  => 'Rey',
                'birthdate'  => '02.02.2008', // 18 years old (valid)
                'run_id'     => (string) $adultRun->id,
            ],
        ]);

        // Verify initial setup
        $this->assertEquals((string) $adultRun->id, $comp->get('elements')[0]['run_id']);

        // Set birthdate to 2018 (8 years old) and trigger integrity check
        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Marc',
                'last_name'  => 'Rey',
                'birthdate'  => '02.02.2018',
                'run_id'     => (string) $adultRun->id,
            ],
        ]);
        $comp->call('verifyIntegrity');

        $errors = $comp->get('integrityErrors');
        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function it_adds_and_removes_rows_in_reactive_table()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $initialCount = count($comp->get('elements'));
        $comp->call('addRow');
        $this->assertCount($initialCount + 1, $comp->get('elements'));

        $comp->call('removeRow', 0);
        $this->assertCount($initialCount, $comp->get('elements'));
    }

    /** @test */
    public function it_toggles_import_modal_state()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        $this->assertFalse($comp->get('showImportModal'));
        $comp->call('openImportModal');
        $this->assertTrue($comp->get('showImportModal'));

        $comp->call('closeImportModal');
        $this->assertFalse($comp->get('showImportModal'));
    }

    /** @test */
    public function it_filters_available_runs_by_gender_and_age()
    {
        $runGirls = Run::create([
            'name'                => 'Cadettes A (3 tours)',
            'gender'              => 'F',
            'min_age'             => 14,
            'max_age'             => 17,
            'available_for_types' => ['group'],
        ]);

        $runBoys = Run::create([
            'name'                => 'Cadets A (4 tours)',
            'gender'              => 'M',
            'min_age'             => 14,
            'max_age'             => 17,
            'available_for_types' => ['group'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        // Person born in 2009 (17 years old in 2026) Female
        $runsForFemale = $comp->instance()->getRunsForBirthdate('01.01.2009', 'F');
        $this->assertArrayHasKey((string) $runGirls->id, $runsForFemale);
        $this->assertArrayNotHasKey((string) $runBoys->id, $runsForFemale);

        // Person born in 2009 Male
        $runsForMale = $comp->instance()->getRunsForBirthdate('01.01.2009', 'M');
        $this->assertArrayHasKey((string) $runBoys->id, $runsForMale);
        $this->assertArrayNotHasKey((string) $runGirls->id, $runsForMale);
    }

    /** @test */
    public function it_auto_fills_and_creates_schools_properly()
    {
        $school = School::create([
            'name'        => 'Centre scolaire de St-Guérin',
            'postal_code' => '1950',
            'locality'    => 'Sion',
            'country'     => 'SUI',
            'is_active'   => true,
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school']);
        $comp->set('data.school_id', (string) $school->id);

        $this->assertEquals('Centre scolaire de St-Guérin', $comp->get('data.school_name'));
        $this->assertEquals('1950', $comp->get('data.school_postal_code'));
        $this->assertEquals('Sion', $comp->get('data.school_locality'));
    }

    /** @test */
    public function it_updates_courses_dynamically_when_gender_changes_and_computes_school_team_stats()
    {
        $runGirls = Run::create([
            'name'                => 'Cadettes A (3 tours)',
            'gender'              => 'F',
            'min_age'             => 14,
            'max_age'             => 17,
            'available_for_types' => ['group'],
        ]);

        $runBoys = Run::create([
            'name'                => 'Cadets A (4 tours)',
            'gender'              => 'M',
            'min_age'             => 14,
            'max_age'             => 17,
            'available_for_types' => ['group'],
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);

        // Set birthdate to 2009 and gender to M
        $comp->set('elements.0.birthdate', '01.01.2009');
        $comp->set('elements.0.gender', 'M');

        // Course should be auto-selected to Boys run since it's the unique match
        $this->assertEquals((string) $runBoys->id, $comp->get('elements.0.run_id'));

        // Change gender to F
        $comp->set('elements.0.gender', 'F');

        // Course should now automatically switch to Girls run
        $this->assertEquals((string) $runGirls->id, $comp->get('elements.0.run_id'));

        // Test school team stats
        $compSchool = Livewire::test(FrontRunRegistration::class, ['type' => 'school']);
        $compSchool->set('elements', [
            ['_k' => '1', 'first_name' => 'Fille1', 'last_name' => 'A', 'gender' => 'F'],
            ['_k' => '2', 'first_name' => 'Fille2', 'last_name' => 'B', 'gender' => 'F'],
            ['_k' => '3', 'first_name' => 'Garcon1', 'last_name' => 'C', 'gender' => 'M'],
        ]);

        $stats = $compSchool->get('schoolTeamStats');
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['girls']);
        $this->assertEquals(1, $stats['boys']);
        $this->assertFalse($stats['is_conform']);
        $this->assertEquals(5, $stats['missing_students']); // 8 - 3 = 5
        $this->assertEquals(1, $stats['missing_girls']);    // 3 - 2 = 1
    }

    /** @test */
    public function it_saves_elements_correctly_without_duplicating_or_resurrecting_deleted_rows()
    {
        $run = Run::factory()->create([
            'name'                => 'Course Entreprises',
            'available_for_types' => ['company'],
            'min_age'             => 16,
            'max_age'             => 99,
        ]);
        setting(['default_run_company' => $run->id]);

        $registration = RunRegistration::factory()->create([
            'run_registration_type'  => 'company',
            'company_name'           => 'Test Enterprise',
            'contact_first_name'     => 'Marc',
            'contact_last_name'      => 'Favre',
            'contact_email'          => 'marc@test.com',
            'invoicing_company_name' => 'Test Enterprise',
            'invoicing_address'      => 'Rue du Rhône 1',
            'invoicing_postal_code'  => '1950',
            'invoicing_locality'     => 'Sion',
            'invoicing_email'        => 'invoicing@test.com',
        ]);

        $comp = Livewire::test(FrontRunRegistration::class, ['registration' => $registration->id]);

        // Add 2 elements
        $comp->set('elements', [
            [
                '_k'         => 'l1',
                'first_name' => 'Alice',
                'last_name'  => 'Dupond',
                'birthdate'  => '15.05.1990',
                'gender'     => 'F',
                'email'      => 'alice@test.com',
            ],
            [
                '_k'         => 'l2',
                'first_name' => 'Bob',
                'last_name'  => 'Martin',
                'birthdate'  => '20.08.1985',
                'gender'     => 'M',
                'email'      => 'bob@test.com',
            ],
        ]);

        // First Save
        $comp->call('save');
        $this->assertCount(2, $registration->fresh()->runRegistrationElements);

        // Consecutive Save without page reload: should not duplicate
        $comp->call('save');
        $this->assertCount(2, $registration->fresh()->runRegistrationElements);

        // Remove 1 element (Bob)
        $comp->call('removeRow', 1);
        $this->assertCount(1, $comp->get('elements'));

        // Save after removal
        $comp->call('save');
        $this->assertCount(1, $registration->fresh()->runRegistrationElements);
        $this->assertEquals('Alice', $registration->fresh()->runRegistrationElements->first()->first_name);

        // Reload fresh component (simulate page refresh)
        $freshComp = Livewire::test(FrontRunRegistration::class, ['registration' => $registration->id]);
        $this->assertCount(1, $freshComp->get('elements'));
        $this->assertEquals('Alice', $freshComp->get('elements.0.first_name'));
    }
}
