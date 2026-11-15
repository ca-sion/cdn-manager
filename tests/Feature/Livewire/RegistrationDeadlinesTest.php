<?php

namespace Tests\Feature\Livewire;

use App\Models\Run;
use Tests\TestCase;
use Livewire\Livewire;
use Illuminate\Support\Carbon;
use App\Livewire\FrontRunRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    private function makeRun(string $name, ?string $deadline = null, array $extra = []): Run
    {
        return Run::create(array_merge([
            'name' => $name, 'available_for_types' => ['group'], 'registrations_deadline' => $deadline,
        ], $extra));
    }

    private function global(?string $deadline): void
    {
        setting(['registrations_deadline' => $deadline]);
    }

    /** @test */
    public function a_run_without_its_own_deadline_follows_the_global_one()
    {
        $this->global(now('Europe/Zurich')->addDay()->format('Y-m-d H:i:s'));
        $this->assertTrue($this->makeRun('A')->isRegistrationOpen());

        $this->global(now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'));
        $this->assertFalse($this->makeRun('B')->isRegistrationOpen());
    }

    /** @test */
    public function a_run_deadline_can_close_earlier_than_the_global_one()
    {
        $this->global(now('Europe/Zurich')->addDays(10)->format('Y-m-d H:i:s'));

        $this->assertFalse($this->makeRun('Courte', now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'))->isRegistrationOpen());
        $this->assertTrue($this->makeRun('Ouverte', now('Europe/Zurich')->addDays(2)->format('Y-m-d H:i:s'))->isRegistrationOpen());
    }

    /** @test */
    public function a_run_deadline_can_never_extend_beyond_the_global_one()
    {
        $this->global(now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'));

        $this->assertFalse($this->makeRun('Tardive', now('Europe/Zurich')->addDays(5)->format('Y-m-d H:i:s'))->isRegistrationOpen());
    }

    /** @test */
    public function no_deadline_at_all_means_open()
    {
        $this->global(null);

        $this->assertTrue($this->makeRun('Libre')->isRegistrationOpen());
    }

    /** @test */
    public function closed_runs_are_shown_greyed_with_a_mention_but_never_auto_selected()
    {
        $this->global(now('Europe/Zurich')->addDays(10)->format('Y-m-d H:i:s'));
        $open = $this->makeRun('Course ouverte');
        $closed = $this->makeRun('Course close', now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'));

        $c = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);
        $options = $c->instance()->getRunsForBirthdate('02.02.1990', 'M', true);

        $this->assertStringContainsString('inscriptions closes', $options[(string) $closed->id]);
        $this->assertArrayNotHasKey((string) $closed->id, $c->instance()->getRunsForBirthdate('02.02.1990', 'M'));

        $c->set('elements.0.first_name', 'Lucas')->set('elements.0.birthdate', '02.02.1990');
        $this->assertSame((string) $open->id, $c->get('elements.0.run_id'));
        $c->assertSee('inscriptions closes');
    }

    /** @test */
    public function a_row_on_a_closed_run_is_locked_and_cannot_be_removed()
    {
        $this->global(null);
        $closed = $this->makeRun('Course close', now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'));
        $open = $this->makeRun('Course ouverte');

        $c = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);
        $c->set('elements', [
            ['_k' => 'a', 'first_name' => 'Lucas', 'last_name' => 'Rey', 'birthdate' => '02.02.1990', 'gender' => 'M', 'email' => 'l@x.ch', 'run_id' => (string) $closed->id],
            ['_k' => 'b', 'first_name' => 'Emma', 'last_name' => 'Dubois', 'birthdate' => '02.02.1990', 'gender' => 'F', 'email' => 'e@x.ch', 'run_id' => (string) $open->id],
        ]);

        $this->assertTrue($c->instance()->isRowLocked($c->get('elements.0')));
        $this->assertFalse($c->instance()->isRowLocked($c->get('elements.1')));

        $c->call('removeRowByKey', 'a');
        $this->assertCount(2, $c->get('elements'));
        $c->call('removeRowByKey', 'b');
        $this->assertCount(1, $c->get('elements'));
    }

    /** @test */
    public function a_new_row_on_a_closed_run_is_refused_by_the_integrity_check()
    {
        $this->global(null);
        $closed = $this->makeRun('Course close', now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'));

        $c = Livewire::test(FrontRunRegistration::class, ['type' => 'group']);
        $c->set('elements', [
            ['_k' => 'a', 'first_name' => 'Lucas', 'last_name' => 'Rey', 'birthdate' => '02.02.1990', 'gender' => 'M', 'email' => 'l@x.ch', 'run_id' => (string) $closed->id],
        ]);
        $c->call('verifyIntegrity');

        $this->assertStringContainsString('Inscriptions closes', json_encode($c->get('integrityErrors')));
    }

    /** @test */
    public function the_whole_school_form_is_locked_when_its_default_run_is_closed()
    {
        $this->global(null);
        $school = $this->makeRun('Interclasses', now('Europe/Zurich')->subHour()->format('Y-m-d H:i:s'), ['available_for_types' => ['school']]);
        setting(['default_run_school' => (string) $school->id]);

        $c = Livewire::test(FrontRunRegistration::class, ['type' => 'school']);

        $this->assertTrue($c->instance()->isGridLocked());
        $c->assertSee('Les inscriptions pour cette course sont closes')->assertSee('Interclasses');
    }

    /** @test */
    public function the_global_deadline_message_shows_the_closing_date()
    {
        $this->global('2026-11-14 11:13:14');
        Carbon::setTestNow('2026-11-15 09:00:00');

        Livewire::test(FrontRunRegistration::class, ['type' => 'group'])->assertSee('14.11.2026');
    }

    /** @test */
    public function the_global_deadline_can_be_edited_in_the_filament_settings_page()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        Livewire::test(\App\Filament\Pages\Settings::class)
            ->assertFormFieldExists('registrations_deadline')
            ->fillForm(['registrations_deadline' => '2026-12-31 23:59:00', 'banana_bank_account' => '1020', 'banana_client_account' => '1100', 'banana_supplier_account' => '2000', 'banana_allocation_mode' => 'client_account'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('2026-12-31 23:59:00', \Carbon\Carbon::parse(setting('registrations_deadline'))->format('Y-m-d H:i:s'));
    }
}
