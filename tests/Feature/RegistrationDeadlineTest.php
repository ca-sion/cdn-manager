<?php

namespace Tests\Feature;

use App\Models\Run;
use Tests\TestCase;
use Livewire\Livewire;
use Illuminate\Support\Carbon;
use App\Livewire\FrontRunRegistration;
use App\Services\RunRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Le délai d'inscription est une heure SUISSE : « 18:00 » ferme à 18:00 à Zurich,
 * pas à 18:00 UTC (soit 19:00 ou 20:00 à Zurich).
 */
class RegistrationDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private function runWithDeadline(string $deadline): Run
    {
        return Run::factory()->create(['registrations_deadline' => $deadline]);
    }

    private function gridLockedAt(string $utcNow, string $deadline): bool
    {
        $this->travelTo(Carbon::parse($utcNow, 'UTC'));
        setting(['registrations_deadline' => $deadline]);

        return Livewire::test(FrontRunRegistration::class, ['type' => 'school'])->instance()->isGridLocked();
    }

    // ───────────── Échéance d'une course ─────────────

    public function test_in_summer_the_run_deadline_closes_at_swiss_time(): void
    {
        $service = app(RunRegistrationService::class);

        // 17:30 UTC = 19:30 à Zurich (heure d'été)
        $this->travelTo(Carbon::parse('2026-07-10 17:30:00', 'UTC'));

        $this->assertFalse($service->isRegistrationOpen($this->runWithDeadline('2026-07-10 19:00:00')), '19:00 suisse est passé');
        $this->assertTrue($service->isRegistrationOpen($this->runWithDeadline('2026-07-10 20:00:00')), '20:00 suisse n\'est pas atteint');
    }

    public function test_in_winter_the_run_deadline_closes_at_swiss_time(): void
    {
        $service = app(RunRegistrationService::class);

        // 17:30 UTC = 18:30 à Zurich (heure d'hiver)
        $this->travelTo(Carbon::parse('2026-12-10 17:30:00', 'UTC'));

        $this->assertFalse($service->isRegistrationOpen($this->runWithDeadline('2026-12-10 18:00:00')));
        $this->assertTrue($service->isRegistrationOpen($this->runWithDeadline('2026-12-10 19:00:00')));
    }

    public function test_deadline_is_exactly_the_swiss_minute(): void
    {
        $service = app(RunRegistrationService::class);
        $run = $this->runWithDeadline('2026-07-10 18:00:00');

        $this->travelTo(Carbon::parse('2026-07-10 15:59:59', 'UTC')); // 17:59:59 Zurich
        $this->assertTrue($service->isRegistrationOpen($run));

        $this->travelTo(Carbon::parse('2026-07-10 16:00:01', 'UTC')); // 18:00:01 Zurich
        $this->assertFalse($service->isRegistrationOpen($run));
    }

    public function test_run_without_deadline_stays_open_and_the_stored_value_is_untouched(): void
    {
        $service = app(RunRegistrationService::class);
        $open = Run::factory()->create(['registrations_deadline' => null]);
        $this->assertTrue($service->isRegistrationOpen($open));
        $this->assertNull($open->registrationsDeadlineAt());

        $run = $this->runWithDeadline('2026-07-10 18:00:00');
        $this->assertSame('2026-07-10 18:00:00', $run->fresh()->registrations_deadline->format('Y-m-d H:i:s'), 'la valeur saisie n\'est pas modifiée');
        $this->assertSame('Europe/Zurich', $run->registrationsDeadlineAt()->getTimezone()->getName());
    }

    // ───────────── Verrouillage de la grille d'inscription ─────────────

    public function test_grid_locks_at_the_swiss_deadline_in_summer(): void
    {
        // 17:30 UTC = 19:30 à Zurich : une échéance à 19:00 est dépassée, une à 20:00 non
        $this->assertTrue($this->gridLockedAt('2026-07-10 17:30:00', '2026-07-10 19:00:00'));
        $this->assertFalse($this->gridLockedAt('2026-07-10 17:30:00', '2026-07-10 20:00:00'));
    }

    public function test_grid_locks_at_the_swiss_deadline_in_winter(): void
    {
        // 17:30 UTC = 18:30 à Zurich
        $this->assertTrue($this->gridLockedAt('2026-12-10 17:30:00', '2026-12-10 18:00:00'));
        $this->assertFalse($this->gridLockedAt('2026-12-10 17:30:00', '2026-12-10 19:00:00'));
    }

    public function test_grid_is_never_locked_without_a_deadline(): void
    {
        $this->travelTo(Carbon::parse('2026-07-10 17:30:00', 'UTC'));
        setting(['registrations_deadline' => null]);

        $this->assertFalse(Livewire::test(FrontRunRegistration::class, ['type' => 'school'])->instance()->isGridLocked());
    }
}
