<?php

namespace Tests\Feature\Filament;

use App\Models\Run;
use Tests\TestCase;
use App\Models\User;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Product;
use App\Support\VatRates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\RunResource\Pages\EditRun;
use App\Filament\Resources\RunResource\Pages\ListRuns;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;

class LabelsTimezoneVatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $edition = Edition::create(['year' => 2025, 'name' => '2025']);
        session(['edition_id' => $edition->id]);
        config(['cdn.default_edition_id' => $edition->id]);
        $this->actingAs(User::factory()->create());
    }

    // ───────────── Libellés ─────────────

    public function test_client_columns_have_explicit_french_labels(): void
    {
        $table = Livewire::test(ListClients::class)->instance()->getTable();

        $expected = [
            'category.name'     => 'Catégorie', 'name' => 'Nom', 'long_name' => 'Raison sociale', 'logo' => 'Logo',
            'email'             => 'E-mail', 'phone' => 'Téléphone', 'address' => 'Adresse',
            'address_extension' => 'Complément d\'adresse', 'locality' => 'Localité', 'postal_code' => 'NPA',
        ];

        foreach ($expected as $name => $label) {
            $this->assertSame($label, ($table->getColumns()[$name] ?? null)?->getLabel(), "libellé de la colonne {$name}");
        }
    }

    public function test_document_columns_have_explicit_french_labels(): void
    {
        $table = Livewire::test(ListDocuments::class)->instance()->getTable();

        $expected = [
            'edition.year'      => 'Édition', 'date' => 'Date', 'validity_year_start' => 'Début de validité',
            'validity_year_end' => 'Fin de validité', 'note' => 'Note', 'created_at' => 'Créé le', 'updated_at' => 'Modifié le',
        ];

        foreach ($expected as $name => $label) {
            $this->assertSame($label, ($table->getColumns()[$name] ?? null)?->getLabel(), "libellé de la colonne {$name}");
        }
    }

    public function test_edition_is_always_written_with_its_accent_in_labels(): void
    {
        $offenders = collect(File::allFiles(app_path()))
            ->filter(fn ($file) => str_contains($file->getContents(), "->label('Edition')"))
            ->map(fn ($file) => $file->getRelativePathname());

        $this->assertTrue($offenders->isEmpty(), 'Libellé « Edition » sans accent dans : '.$offenders->implode(', '));
    }

    // ───────────── Fuseau horaire ─────────────

    public function test_panel_displays_dates_in_swiss_time_while_the_database_stays_utc(): void
    {
        $this->assertSame('Europe/Zurich', FilamentTimezone::get());
        $this->assertSame('UTC', config('app.timezone'), 'le fuseau de l\'application et de la base ne change pas');

        // 12:00 UTC en été = 14:00 à Zurich ; en hiver = 13:00
        $summer = TextColumn::make('x')->dateTime('H:i')->timezone(FilamentTimezone::get());
        $this->assertSame('Europe/Zurich', $summer->getTimezone());
        $this->assertSame('14:00', Carbon::parse('2026-07-01 12:00:00', 'UTC')->setTimezone($summer->getTimezone())->format('H:i'));
        $this->assertSame('13:00', Carbon::parse('2026-12-01 12:00:00', 'UTC')->setTimezone($summer->getTimezone())->format('H:i'));
    }

    public function test_datetime_columns_without_explicit_timezone_use_the_swiss_time(): void
    {
        $this->assertSame('Europe/Zurich', TextColumn::make('created_at')->dateTime()->getTimezone());
    }

    // ───────────── TVA ─────────────

    public function test_vat_list_shows_2_6_not_2_1_and_offers_exempt(): void
    {
        $options = VatRates::options();

        $this->assertSame('2.6', $options['2.6']);
        $this->assertNotContains('2.1', $options);
        $this->assertEquals(['8.1', '3.8', '2.6', '0'], array_keys($options));
        $this->assertSame('0 (exonéré)', $options['0']);
    }

    public function test_no_form_still_defines_its_own_vat_list(): void
    {
        $offenders = collect(File::allFiles(app_path()))
            ->filter(fn ($file) => str_contains($file->getContents(), "'2.6' => '2.1'"))
            ->map(fn ($file) => $file->getRelativePathname());

        $this->assertTrue($offenders->isEmpty(), 'Liste de TVA locale dans : '.$offenders->implode(', '));
    }

    public function test_product_with_exempt_vat_loads_and_saves_without_losing_its_rate(): void
    {
        $product = Product::factory()->create(['tax_rate' => 0]);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->assertFormSet(['tax_rate' => 0])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(0, $product->fresh()->tax_rate);
    }

    public function test_product_keeps_a_reduced_rate(): void
    {
        $product = Product::factory()->create(['tax_rate' => 2.6]);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->assertFormSet(['tax_rate' => 2.6])
            ->fillForm(['tax_rate' => '3.8'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(3.8, $product->fresh()->tax_rate);
    }

    public function test_hand_typed_times_are_never_shifted_by_the_swiss_display_timezone(): void
    {
        // Heures saisies par le personnel : stockées telles quelles, elles ne doivent pas glisser de 1 à 2 h
        $run = Run::factory()->create(['registrations_deadline' => '2026-07-10 18:00:00']);

        Livewire::test(EditRun::class, ['record' => $run->getKey()])
            ->assertFormSet(['registrations_deadline' => '2026-07-10 18:00:00'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('2026-07-10 18:00:00', $run->fresh()->registrations_deadline->format('Y-m-d H:i:s'));

        $table = Livewire::test(ListRuns::class)->instance()->getTable();
        $this->assertSame('UTC', $table->getColumns()['registrations_deadline']->getTimezone());

        $provisions = Livewire::test(ListProvisionElements::class)->instance()->getTable();
        $this->assertSame('UTC', $provisions->getColumns()['contact_time']->getTimezone());
    }

    public function test_system_timestamps_are_displayed_in_swiss_time(): void
    {
        $table = Livewire::test(ListDocuments::class)->instance()->getTable();

        $this->assertSame('Europe/Zurich', $table->getColumns()['created_at']->getTimezone());
    }
}
