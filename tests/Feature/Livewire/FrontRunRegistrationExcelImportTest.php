<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\XLSX\Writer;
use Illuminate\Http\UploadedFile;
use App\Livewire\FrontRunRegistration;
use OpenSpout\Common\Entity\Style\Style;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FrontRunRegistrationExcelImportTest extends TestCase
{
    use RefreshDatabase;

    /** Construit un vrai .xlsx ; une valeur DateTimeInterface devient une vraie cellule date Excel. */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        $w = new Writer;
        $w->openToFile($path);
        foreach ($rows as $row) {
            $w->addRow(new Row(array_map(
                fn ($v) => $v instanceof \DateTimeInterface
                    ? Cell::fromValue($v, (new Style)->setFormat('dd.mm.yyyy'))
                    : Cell::fromValue($v),
                $row
            )));
        }
        $w->close();

        return UploadedFile::fake()->createWithContent('liste.xlsx', file_get_contents($path));
    }

    private function import(UploadedFile $file)
    {
        return Livewire::test(FrontRunRegistration::class, ['type' => 'group'])
            ->set('importFile', $file)
            ->call('processExcelImport');
    }

    private function people($component): array
    {
        return collect($component->get('elements'))
            ->filter(fn ($e) => $e['first_name'] !== '')
            ->map(fn ($e) => [$e['first_name'], $e['last_name'], $e['birthdate'], $e['gender'], $e['email']])
            ->values()->all();
    }

    /** @test */
    public function it_imports_real_excel_date_cells()
    {
        $c = $this->import($this->xlsx([
            ['Nom', 'Prénom', 'Date de naissance', 'Sexe'],
            ['Rey', 'Lucas', new \DateTimeImmutable('2014-02-02'), 'G'],
            ['Dubois', 'Emma', new \DateTimeImmutable('2012-08-15'), 'Fille'],
        ]));

        $c->assertHasNoErrors();
        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', ''],
            ['Emma', 'Dubois', '15.08.2012', 'F', ''],
        ], $this->people($c));
    }

    /** @test */
    public function it_skips_title_rows_before_the_headers()
    {
        $c = $this->import($this->xlsx([
            ['Ville de Sion - Liste des participants'],
            [],
            ['Prénom', 'Nom', 'Date de naissance', 'Genre', 'E-mail'],
            ['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch'],
        ]));

        $c->assertHasNoErrors();
        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch']], $this->people($c));
    }

    /** @test */
    public function it_accepts_the_headers_announced_in_the_modal()
    {
        $c = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Date de naissance (jj.mm.aaaa)', 'Genre (M/F)', 'Email', 'Nationalité (ex: SUI)'],
            ['Emma', 'Dubois', '15.08.2008', 'F', 'e@x.ch', 'FRA'],
        ]));

        $c->assertHasNoErrors();
        $this->assertSame([['Emma', 'Dubois', '15.08.2008', 'F', 'e@x.ch']], $this->people($c));
        $this->assertSame('FRA', collect($c->get('elements'))->firstWhere('first_name', 'Emma')['nationality']);
    }

    /** @test */
    public function it_converts_excel_serial_numbers_and_keeps_plain_years()
    {
        $c = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Naissance'],
            ['Lucas', 'Rey', 43133],
            ['Emma', 'Dubois', 2012],
        ]));

        $this->assertSame('02.02.2018', $this->people($c)[0][2]);
        $this->assertSame('2012', $this->people($c)[1][2]);
    }

    /** @test */
    public function it_falls_back_to_column_order_without_headers()
    {
        $c = $this->import($this->xlsx([
            ['Lucas', 'Rey', '02.02.2014', 'M'],
        ]));

        $this->assertSame([['Lucas', 'Rey', '02.02.2014', 'M', '']], $this->people($c));
    }

    /** @test */
    public function it_reports_an_error_when_nothing_is_recognized()
    {
        $c = $this->import($this->xlsx([['Prénom', 'Nom']]));

        $c->assertHasErrors('importFile');
    }

    /** @test */
    public function the_downloadable_template_can_be_reimported()
    {
        $response = Livewire::test(FrontRunRegistration::class, ['type' => 'group'])
            ->call('downloadImportTemplate')->effects['download'];

        $content = base64_decode($response['content']);
        $this->assertNotEmpty($content);

        $c = $this->import(UploadedFile::fake()->createWithContent('modele.xlsx', $content));
        $c->assertHasNoErrors();
        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', 'lucas@example.com'],
            ['Emma', 'Dubois', '15.08.2012', 'F', ''],
        ], $this->people($c));
    }

    // ------------------------------------------------------------------
    // Cas réalistes : fichiers « sales » reçus de clients
    // ------------------------------------------------------------------

    private function makeRun(string $name, array $attrs = []): \App\Models\Run
    {
        return \App\Models\Run::create(array_merge([
            'name'                => $name,
            'available_for_types' => ['group'],
        ], $attrs));
    }

    private function elements($component): array
    {
        return collect($component->get('elements'))->filter(fn ($e) => $e['first_name'] !== '')->values()->all();
    }

    /** @test */
    public function it_does_not_import_an_info_block_above_the_table_as_participants()
    {
        // Reproduit la capture : bloc « formulaire » libellé/valeur puis tableau plus bas
        $c = $this->import($this->xlsx([
            ['Contact', 'Ville de Sion'],
            ['Société/Club', 'Ville de Sion'],
            ['Personne de contact', 'Jean Dupont'],
            ['Email de contact', 'jean@sion.ch'],
            ['Adresse de facturation', ''],
            ['Raison sociale', 'Ville de Sion'],
            ['Adresse postale', 'Rue du Rhône', 8],
            ['Adresse postale (2e ligne)', ''],
            ['NPA', 1950],
            ['Localité', 'Sion'],
            ['Email de facturation', 'compta@sion.ch'],
            ['Note', ''],
            [],
            ['Nom', 'Prénom', 'Date de naissance', 'Genre', 'Email', 'Course'],
            ['Rey', 'Lucas', '02.02.2014', 'M', 'lucas@sion.ch', ''],
            ['Dubois', 'Emma', '15.08.2012', 'F', 'emma@sion.ch', ''],
        ]));

        $c->assertHasNoErrors();
        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', 'lucas@sion.ch'],
            ['Emma', 'Dubois', '15.08.2012', 'F', 'emma@sion.ch'],
        ], $this->people($c));
        $this->assertCount(2, array_filter($c->get('elements'), fn ($e) => trim($e['last_name']) !== ''));
    }

    /** @test */
    public function a_lone_nom_cell_in_an_info_block_is_not_a_header()
    {
        $c = $this->import($this->xlsx([
            ['Nom', 'Ville de Sion'],
            ['Contact', 'Jean'],
            ['Adresse postale', 'Rue du Rhône', 8],
            ['NPA', 1950],
        ]));

        $c->assertHasErrors('importFile');
        $this->assertSame([], $this->people($c));
    }

    /** @test */
    public function it_handles_columns_in_any_order_with_extra_columns()
    {
        $c = $this->import($this->xlsx([
            ['N°', 'E-mail', 'Adresse', 'NPA', 'Prénom', 'Téléphone', 'Nom', 'Sexe', 'Année de naissance'],
            [1, 'l@x.ch', 'Rue 1', 1950, 'Lucas', '079 000 00 00', 'Rey', 'Garçon', 2014],
            [2, 'e@x.ch', 'Rue 2', 1951, 'Emma', '', 'Dubois', 'Fille', 2012],
        ]));

        $c->assertHasNoErrors();
        $this->assertSame([
            ['Lucas', 'Rey', '2014', 'M', 'l@x.ch'],
            ['Emma', 'Dubois', '2012', 'F', 'e@x.ch'],
        ], $this->people($c));
    }

    /** @test */
    public function it_skips_blank_and_total_lines_between_participants()
    {
        $c = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Date de naissance', 'Genre', 'E-mail'],
            ['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch'],
            [],
            ['', '', '', '', ''],
            ['Emma', 'Dubois', '15.08.2012', 'F', 'e@x.ch'],
            ['Total', '', '', '', 2],
        ]));

        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch'],
            ['Emma', 'Dubois', '15.08.2012', 'F', 'e@x.ch'],
        ], $this->people($c));
    }

    /** @test */
    public function it_normalizes_assorted_date_formats_and_keeps_only_the_first_email()
    {
        $c = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Date de naissance', 'E-mail'],
            ['A', 'One', '2014-02-03', 'a@x.ch, b@x.ch'],
            ['B', 'Two', '3/2/2014', 'c@x.ch d@x.ch'],
            ['C', 'Three', '3.2.14', 'pas un mail'],
            ['D', 'Four', '2014-02-03 00:00:00', 'E@X.CH'],
        ]));

        $p = $this->people($c);
        $this->assertSame(['03.02.2014', 'a@x.ch'], [$p[0][2], $p[0][4]]);
        $this->assertSame(['03.02.2014', 'c@x.ch'], [$p[1][2], $p[1][4]]);
        $this->assertSame(['03.02.2014', ''], [$p[2][2], $p[2][4]]);
        $this->assertSame(['03.02.2014', 'E@X.CH'], [$p[3][2], $p[3][4]]);
    }

    /** @test */
    public function it_matches_the_course_column_by_name_code_and_ignores_unknown_ones()
    {
        $a = $this->makeRun('Course Hommes -4.30', ['gender' => 'M', 'code' => 'H-']);
        $b = $this->makeRun('Course Hommes +4.30', ['gender' => 'M', 'code' => 'H+']);
        $c = $this->makeRun('Trail des Châteaux');

        $component = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Date de naissance', 'Genre', 'Course'],
            ['Lucas', 'Rey', '02.02.1990', 'M', 'course hommes +4.30'],
            ['Paul', 'Roux', '02.02.1990', 'M', 'Course Hommes -4.30'],
            ['Marc', 'Blanc', '02.02.1990', 'M', 'trail des chateaux'],
            ['Jean', 'Noir', '02.02.1990', 'M', 'H+'],
            ['Luc', 'Gris', '02.02.1990', 'M', 'Course inconnue'],
        ]));

        $e = $this->elements($component);
        $this->assertSame((string) $b->id, $e[0]['run_id']);
        $this->assertSame((string) $a->id, $e[1]['run_id']);
        $this->assertSame((string) $c->id, $e[2]['run_id']);
        $this->assertSame((string) $b->id, $e[3]['run_id']);
        $this->assertSame('', $e[4]['run_id']);
    }

    /** @test */
    public function it_reads_semicolon_csv_files()
    {
        $csv = "Prénom;Nom;Date de naissance;Genre;E-mail\r\nLucas;Rey;02.02.2014;M;l@x.ch\r\nEmma;Dubois;15.08.2012;F;\r\n";
        $file = UploadedFile::fake()->createWithContent('liste.csv', $csv);

        $c = $this->import($file);

        $c->assertHasNoErrors();
        $this->assertSame([
            ['Lucas', 'Rey', '02.02.2014', 'M', 'l@x.ch'],
            ['Emma', 'Dubois', '15.08.2012', 'F', ''],
        ], $this->people($c));
    }

    /** @test */
    public function it_imports_a_long_file()
    {
        $rows = [['Prénom', 'Nom', 'Date de naissance', 'Genre', 'Email']];
        for ($i = 1; $i <= 60; $i++) {
            $rows[] = ["P$i", "N$i", '02.02.2014', $i % 2 ? 'M' : 'F', "p$i@x.ch"];
        }

        $this->assertCount(60, $this->people($this->import($this->xlsx($rows))));
    }

    /** @test */
    public function it_tells_the_user_what_was_skipped()
    {
        $c = $this->import($this->xlsx([
            ['Prénom', 'Nom', 'Date de naissance', 'Course'],
            ['Lucas', 'Rey', '02.02.2014', 'Inconnue'],
            ['Total', '', '', ''],
        ]));

        $c->assertSee('1 participant importé');
        $c->assertSee('1 ligne(s) ignorée(s)');
        $c->assertSee('1 course(s) non reconnue(s)');
    }
}
