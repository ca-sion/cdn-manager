<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Run;
use App\Models\Client;
use App\Models\Edition;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Enums\RunRegistrationType;
use App\Enums\Gender;
use App\Filament\Resources\RunRegistrationResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RunRegistrationExportsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_generate_datasport_school_excel_download_response()
    {
        $regSchool = RunRegistration::create([
            'run_registration_type'          => RunRegistrationType::School,
            'school_name'                    => 'Sacré-Cœur',
            'school_class_level'             => '8H',
            'school_class_holder_first_name' => 'Isabelle',
            'school_class_holder_last_name'  => 'Emery',
            'contact_first_name'             => 'Isabelle',
            'contact_last_name'              => 'Emery',
            'contact_email'                  => 'isabelle@sacrecoeur.ch',
        ]);

        $run = Run::factory()->create(['name' => 'Course Écoles 3km']);

        RunRegistrationElement::create([
            'run_registration_id' => $regSchool->id,
            'first_name'          => 'Lucas',
            'last_name'           => 'Bessel',
            'birthdate'           => '2014-04-12',
            'gender'              => Gender::Male,
            'run_id'              => $run->id,
        ]);

        $response = RunRegistrationResource::generateDatasportSchoolExcel(collect([$regSchool]));

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_can_generate_datasport_company_excel_download_response()
    {
        $regCompany = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Company,
            'company_name'          => 'UBS Valais',
            'contact_first_name'    => 'Marc',
            'contact_last_name'     => 'Dubuis',
            'contact_email'         => 'marc@ubs.com',
        ]);

        $run = Run::factory()->create(['name' => 'Course Entreprises 10km']);

        RunRegistrationElement::create([
            'run_registration_id' => $regCompany->id,
            'first_name'          => 'Gilles',
            'last_name'           => 'Lausannois',
            'birthdate'           => '1988-02-19',
            'gender'              => Gender::Male,
            'team'                => 'UBS',
            'bloc'                => 'Bloc 2 18h10',
            'with_video'          => false,
            'run_id'              => $run->id,
        ]);

        $response = RunRegistrationResource::generateDatasportCompanyExcel(collect([$regCompany]));

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_can_generate_datasport_group_excel_download_response()
    {
        $regGroup = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Group,
            'contact_first_name'    => 'Antoine',
            'contact_last_name'     => 'Clivaz',
            'contact_email'         => 'antoine@gmail.com',
        ]);

        $run = Run::factory()->create(['name' => 'Ecolier/ère A']);

        RunRegistrationElement::create([
            'run_registration_id' => $regGroup->id,
            'first_name'          => 'Jean-Pierre',
            'last_name'           => 'Pagliaccio',
            'birthdate'           => '1965-09-13',
            'gender'              => Gender::Male,
            'team'                => 'Run Club Grimisuat',
            'with_video'          => false,
            'run_id'              => $run->id,
        ]);

        $response = RunRegistrationResource::generateDatasportGroupExcel(collect([$regGroup]));

        $this->assertNotNull($response);
    }

    /** @test */
    public function it_can_generate_aggregated_accounting_excel_download_response()
    {
        Edition::factory()->create(['year' => (int) date('Y')]);

        $client = Client::factory()->create(['name' => 'BCVS']);

        $regCompany = RunRegistration::create([
            'client_id'             => $client->id,
            'run_registration_type' => RunRegistrationType::Company,
            'company_name'          => 'BCVS Team',
            'contact_first_name'    => 'Pierre',
            'contact_last_name'     => 'Dubois',
            'contact_email'         => 'pierre@bcvs.ch',
        ]);

        $run = Run::factory()->create(['cost' => 35.00]);

        RunRegistrationElement::create([
            'run_registration_id' => $regCompany->id,
            'first_name'          => 'Marc',
            'last_name'           => 'Favre',
            'run_id'              => $run->id,
        ]);

        $response = RunRegistrationResource::generateAggregatedExcel(collect([$regCompany]));

        $this->assertNotNull($response);
    }
}
