<?php

namespace Tests\Feature\Livewire;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\School;
use Livewire\Livewire;
use App\Models\Edition;
use Illuminate\Support\Carbon;
use App\Models\RunRegistration;
use Illuminate\Http\UploadedFile;
use App\Enums\RunRegistrationType;
use Illuminate\Support\Facades\URL;
use App\Livewire\FrontRunRegistration;
use Illuminate\Support\Facades\Storage;
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
            'contact_phone'          => '079 123 45 67',
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

    /** @test */
    public function it_removes_only_the_targeted_row_by_key()
    {
        foreach (['school', 'company'] as $type) {
            $rows = [];
            for ($i = 1; $i <= 15; $i++) {
                $rows[] = ['_k' => "k$i", 'first_name' => "P$i", 'last_name' => "N$i", 'birthdate' => '', 'gender' => 'M'];
            }

            $comp = Livewire::test(FrontRunRegistration::class, ['type' => $type])
                ->set('elements', $rows)
                ->call('removeRowByKey', 'k3');

            $elements = $comp->get('elements');
            $this->assertCount(14, $elements);
            $this->assertEquals('P4', $elements[2]['first_name']);
            $this->assertEquals('P15', $elements[13]['first_name']);
            $this->assertNotContains('k3', array_column($elements, '_k'));
        }
    }

    /** @test */
    public function it_ignores_a_repeated_removal_of_the_same_key()
    {
        $rows = [];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = ['_k' => "k$i", 'first_name' => "P$i", 'last_name' => "N$i", 'birthdate' => '', 'gender' => 'M'];
        }

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school'])
            ->set('elements', $rows)
            ->call('removeRowByKey', 'k2')
            ->call('removeRowByKey', 'k2');

        $this->assertCount(4, $comp->get('elements'));
    }

    /** @test */
    public function it_drops_ghost_rows_created_by_late_updates_after_a_deletion()
    {
        $rows = [];
        for ($i = 1; $i <= 3; $i++) {
            $rows[] = ['_k' => "k$i", 'first_name' => "P$i", 'last_name' => "N$i", 'birthdate' => '', 'gender' => 'M'];
        }

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school'])
            ->set('elements', $rows)
            ->call('removeRowByKey', 'k1')
            ->set('elements.2.first_name', '');

        $elements = $comp->get('elements');
        $this->assertCount(2, $elements);
        $this->assertEquals(['k2', 'k3'], array_column($elements, '_k'));
    }

    /** @test */
    public function it_keeps_a_blank_row_when_the_last_row_is_removed()
    {
        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school'])
            ->set('elements', [['_k' => 'only', 'first_name' => 'A', 'last_name' => 'B', 'birthdate' => '', 'gender' => 'M']])
            ->call('removeRowByKey', 'only');

        $this->assertCount(3, $comp->get('elements'));
    }

    private function fakeRegistration(string $type = 'company', array $overrides = []): RunRegistration
    {
        return RunRegistration::factory()->create(array_merge([
            'run_registration_type'  => $type,
            'company_name'           => 'Test Enterprise',
            'contact_first_name'     => 'Marc',
            'contact_last_name'      => 'Favre',
            'contact_email'          => 'marc@test.com',
            'contact_phone'          => '079 123 45 67',
            'invoicing_company_name' => 'Test Enterprise',
            'invoicing_address'      => 'Rue du Rhône 1',
            'invoicing_postal_code'  => '1950',
            'invoicing_locality'     => 'Sion',
            'invoicing_email'        => 'invoicing@test.com',
        ], $overrides));
    }

    /** @test */
    public function paste_import_does_not_assign_a_group_run_to_company_or_school_rows()
    {
        Run::factory()->create(['name' => 'Course Groupes', 'available_for_types' => ['group'], 'min_age' => 5, 'max_age' => 99]);

        foreach (['company', 'school'] as $type) {
            $comp = Livewire::test(FrontRunRegistration::class, ['type' => $type])
                ->set('pasteTextData', 'Lucas;Rey;02.02.2012;M;lucas@example.com;SUI')
                ->call('processPasteText');

            $imported = collect($comp->get('elements'))->firstWhere('first_name', 'Lucas');
            $this->assertNotNull($imported, "row not imported for $type");
            $this->assertSame('', (string) $imported['run_id'], "run_id must stay empty for $type");
        }
    }

    /** @test */
    public function paste_import_still_auto_selects_the_only_matching_run_for_groups()
    {
        $run = Run::factory()->create(['name' => 'Course Groupes', 'available_for_types' => ['group'], 'min_age' => 5, 'max_age' => 99]);

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'group'])
            ->set('pasteTextData', 'Lucas;Rey;02.02.2012;M;lucas@example.com;SUI')
            ->call('processPasteText');

        $imported = collect($comp->get('elements'))->firstWhere('first_name', 'Lucas');
        $this->assertSame((string) $run->id, (string) $imported['run_id']);
    }

    /** @test */
    public function locked_grid_does_not_prevent_updating_contact_details_even_with_invalid_rows()
    {
        $run = Run::factory()->create(['name' => 'Course Entreprises', 'available_for_types' => ['company']]);
        setting(['default_run_company' => $run->id]);
        $registration = $this->fakeRegistration();

        $comp = Livewire::test(FrontRunRegistration::class, ['registration' => $registration->id]);

        setting(['registrations_deadline' => now()->subDay()->toDateTimeString()]);

        $comp->set('elements', [
            ['_k' => 'l1', 'first_name' => 'Alice', 'last_name' => '', 'birthdate' => 'nope', 'gender' => 'F', 'email' => 'bad'],
        ])->set('data.contact_first_name', 'Zed')->call('save');

        $this->assertSame('Zed', $registration->fresh()->contact_first_name);
        $this->assertCount(0, $registration->fresh()->runRegistrationElements);
    }

    /** @test */
    public function excel_import_rejects_files_that_are_not_spreadsheets()
    {
        Storage::fake('local');

        Livewire::test(FrontRunRegistration::class, ['type' => 'school'])
            ->set('importFile', UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'))
            ->call('processExcelImport')
            ->assertHasErrors(['importFile']);
    }

    /** @test */
    public function a_registration_cannot_be_opened_by_numeric_id_without_a_signed_url()
    {
        $registration = $this->fakeRegistration();

        $response = $this->get('/registrations/'.$registration->id);

        $response->assertDontSee('Test Enterprise');
        $response->assertDontSee('marc@test.com');
    }

    /** @test */
    public function the_edit_route_rejects_unsigned_urls()
    {
        $registration = $this->fakeRegistration();

        $this->get(route('front.run-registration.edit', ['registration' => $registration->id]))
            ->assertStatus(403);
    }

    /** @test */
    public function client_query_string_prefills_the_form_over_http()
    {
        Edition::factory()->create();
        $client = Client::factory()->create(['name' => 'Prefill Corp SA']);

        $this->get('/registrations/company?client_id='.$client->id)
            ->assertOk()
            ->assertSee('Prefill Corp SA');
    }

    /** @test */
    public function client_id_is_kept_on_the_registration_when_saving()
    {
        $run = Run::factory()->create(['name' => 'Course Entreprises', 'available_for_types' => ['company']]);
        setting(['default_run_company' => $run->id]);
        Edition::factory()->create();
        $client = Client::factory()->create();

        Livewire::test(FrontRunRegistration::class, ['type' => 'company'])
            ->set('data.client_id', $client->id)
            ->set('data.company_name', 'Acme')
            ->set('data.invoicing_address', 'Rue 1')
            ->set('data.invoicing_postal_code', '1950')
            ->set('data.invoicing_locality', 'Sion')
            ->set('data.invoicing_company_name', 'Acme')
            ->set('data.invoicing_email', 'acme@test.com')
            ->set('data.contact_first_name', 'A')
            ->set('data.contact_last_name', 'B')
            ->set('data.contact_email', 'ab@test.com')
            ->set('elements', [['_k' => 'l1', 'first_name' => 'Alice', 'last_name' => 'Dupond', 'birthdate' => '15.05.1990', 'gender' => 'F', 'email' => 'a@test.com']])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($client->id, RunRegistration::latest('id')->first()?->client_id);
    }

    /** @test */
    public function a_year_only_birthdate_is_rejected_instead_of_being_saved_as_1970()
    {
        $run = Run::factory()->create(['name' => 'Course Entreprises', 'available_for_types' => ['company']]);
        setting(['default_run_company' => $run->id]);
        $registration = $this->fakeRegistration();

        Livewire::test(FrontRunRegistration::class, ['registration' => $registration->id])
            ->set('elements', [['_k' => 'l1', 'first_name' => 'Alice', 'last_name' => 'Dupond', 'birthdate' => '1990', 'gender' => 'F', 'email' => 'a@test.com']])
            ->call('save');

        $this->assertCount(0, $registration->fresh()->runRegistrationElements);
    }

    /** @test */
    public function a_registration_can_still_be_opened_by_id_through_a_signed_url()
    {
        $registration = $this->fakeRegistration();

        $this->get(URL::signedRoute('front.run-registration.edit', ['registration' => $registration->id]))
            ->assertOk()
            ->assertSee('Test Enterprise');
    }

    /** @test */
    public function age_is_computed_from_the_birth_year_only()
    {
        Carbon::setTestNow('2026-11-07');

        $comp = Livewire::test(FrontRunRegistration::class, ['type' => 'school']);

        $this->assertSame(16, $comp->instance()->calculateAge('31.12.2010'));
        $this->assertSame(16, $comp->instance()->calculateAge('01.01.2010'));
        $this->assertNull($comp->instance()->calculateAge('abc'));

        Carbon::setTestNow();
    }

    /** @test */
    public function grid_rows_are_keyed_by_key_and_index_so_shifted_rows_get_fresh_wire_model_bindings()
    {
        // Regression: wire:model paths embed the index. If a row keeps the same wire:key after its index
        // changes, Livewire 4 keeps the old binding and the grid shows the wrong values (verified in browser).
        $rows = [];
        for ($i = 1; $i <= 3; $i++) {
            $rows[] = ['_k' => "k$i", 'first_name' => "P$i", 'last_name' => "N$i", 'birthdate' => '', 'gender' => 'M'];
        }

        foreach (['school', 'company'] as $type) {
            $comp = Livewire::test(FrontRunRegistration::class, ['type' => $type])->set('elements', $rows);
            $comp->assertSeeHtml('wire:key="element-row-k3-2"');

            $comp->call('removeRowByKey', 'k1');
            $comp->assertSeeHtml('wire:key="element-row-k3-1"')->assertDontSeeHtml('wire:key="element-row-k3-2"');
        }
    }
}
