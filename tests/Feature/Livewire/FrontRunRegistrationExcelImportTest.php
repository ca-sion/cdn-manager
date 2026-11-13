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
}
