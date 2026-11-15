<?php

namespace Tests\Feature\Livewire;

use App\Models\Run;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\FrontRunRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontRunRegistrationPasteImportTest extends TestCase
{
    use RefreshDatabase;

    private function paste(string $text, string $type = 'group')
    {
        return Livewire::test(FrontRunRegistration::class, ['type' => $type])
            ->set('pasteTextData', $text)
            ->call('processPasteText');
    }

    private function people($component): array
    {
        return collect($component->get('elements'))
            ->filter(fn ($e) => $e['first_name'] !== '')
            ->map(fn ($e) => [$e['first_name'], $e['last_name'], $e['birthdate'], $e['gender'], $e['email']])
            ->values()->all();
    }

    private function makeRun(string $name, array $attrs = []): Run
    {
        return Run::create(array_merge(['name' => $name, 'available_for_types' => ['group']], $attrs));
    }

    /** @test */
    public function an_empty_cell_in_the_middle_does_not_shift_the_columns()
    {
        // Copie Excel réelle : le genre est vide, l'e-mail doit rester l'e-mail
        $c = $this->paste("Lucas\tRey\t02.02.2014\t\tlucas@x.ch\tFRA\nEmma\tDubois\t15.08.2012\tF\t\tSUI");

        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', 'lucas@x.ch'],
            ['Emma', 'Dubois', '15.08.2012', 'F', ''],
        ], $this->people($c));
        $e = collect($c->get('elements'))->firstWhere('first_name', 'Lucas');
        $this->assertSame('FRA', $e['nationality']);
    }

    /** @test */
    public function a_pasted_header_row_is_not_imported_as_a_participant()
    {
        $c = $this->paste("Prénom\tNom\tDate de naissance\tGenre\tEmail\nLucas\tRey\t02.02.2014\tM\tl@x.ch");

        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($c));
    }

    /** @test */
    public function pasted_headers_in_another_order_are_honoured()
    {
        $c = $this->paste("Email\tNom\tPrénom\tSexe\tNaissance\nl@x.ch\tRey\tLucas\tGarçon\t2014-02-02");

        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($c));
    }

    /** @test */
    public function the_course_column_is_imported_from_a_paste()
    {
        $a = $this->makeRun('Course Hommes -4.30', ['gender' => 'M']);
        $b = $this->makeRun('Course Hommes +4.30', ['gender' => 'M']);

        $c = $this->paste("Prénom\tNom\tDate de naissance\tGenre\tEmail\tCourse\n"
            ."Lucas\tRey\t02.02.1990\tM\tl@x.ch\tCourse Hommes +4.30\n"
            ."Paul\tRoux\t02.02.1990\tM\tp@x.ch\tcourse hommes -4.30");

        $e = collect($c->get('elements'))->filter(fn ($e) => $e['first_name'] !== '')->values();
        $this->assertSame((string) $b->id, $e[0]['run_id']);
        $this->assertSame((string) $a->id, $e[1]['run_id']);
        $this->assertSame('l@x.ch', $e[0]['email']);
    }

    /** @test */
    public function the_course_is_the_seventh_column_when_there_are_no_headers()
    {
        $b = $this->makeRun('Course Hommes +4.30', ['gender' => 'M']);
        $this->makeRun('Course Hommes -4.30', ['gender' => 'M']);

        $c = $this->paste("Lucas\tRey\t02.02.1990\tM\tl@x.ch\tSUI\tCourse Hommes +4.30");

        $this->assertSame((string) $b->id, collect($c->get('elements'))->firstWhere('first_name', 'Lucas')['run_id']);
    }

    /** @test */
    public function other_separators_and_windows_line_breaks_work()
    {
        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($this->paste("Lucas;Rey;02.02.2014;M;l@x.ch\r\n")));
        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($this->paste('Lucas | Rey | 02.02.2014 | M | l@x.ch')));
        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($this->paste("Lucas,Rey,02.02.2014,M,l@x.ch\r\n")));
    }

    /** @test */
    public function a_comma_inside_a_tab_separated_cell_does_not_break_the_line()
    {
        $c = $this->paste("Lucas\tRey\t02.02.2014\tM\tl@x.ch\tSUI\t\"Rue du Rhône 8, Sion\"");

        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($c));
    }

    /** @test */
    public function names_only_lines_separated_by_spaces_still_work()
    {
        $c = $this->paste("Lucas Rey 02.02.2014\nEmma Dubois 15.08.2012");

        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', ''],
            ['Emma', 'Dubois', '15.08.2012', 'M', ''],
        ], $this->people($c));
    }

    /** @test */
    public function pasting_garbage_shows_an_error_and_imports_nothing()
    {
        $c = $this->paste("Contact\tVille de Sion\nAdresse postale\tRue du Rhône\t8\nNPA\t1950");

        $c->assertHasErrors('pasteTextData');
        $this->assertSame([], $this->people($c));
    }

    /** @test */
    public function the_import_reports_what_it_did()
    {
        $this->makeRun('Course Hommes +4.30', ['gender' => 'M']);
        $this->makeRun('Course Hommes -4.30', ['gender' => 'M']);

        $c = $this->paste("Prénom\tNom\tDate de naissance\tGenre\tCourse\nLucas\tRey\t02.02.1990\tM\tInconnue\nTotal\t\t\t\t");

        $c->assertSee('1 participant importé');
        $c->assertSee('1 ligne(s) ignorée(s)');
        $c->assertSee('1 course(s) non reconnue(s)');
    }

    /** @test */
    public function an_imported_row_with_its_course_passes_the_integrity_check()
    {
        $this->makeRun('Course Hommes +4.30', ['gender' => 'M', 'min_age' => 18]);
        $this->makeRun('Course Hommes -4.30', ['gender' => 'M', 'min_age' => 18]);

        $c = $this->paste("Prénom\tNom\tDate de naissance\tGenre\tEmail\tCourse\nLucas\tRey\t02.02.1990\tM\tl@x.ch\tCourse Hommes +4.30");
        $c->call('verifyIntegrity');

        $this->assertEmpty($c->get('integrityErrors'));
    }

    /** @test */
    public function a_locked_grid_explains_that_the_deadline_has_passed()
    {
        setting(['registrations_deadline' => now('Europe/Zurich')->subDay()->format('Y-m-d H:i:s')]);

        Livewire::test(FrontRunRegistration::class, ['type' => 'group'])
            ->assertSee('Le délai d\'inscription est dépassé');
    }
}
