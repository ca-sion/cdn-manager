<?php

namespace Tests\Unit\Models;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use App\Models\RunRegistrationElement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RunRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'client_id',
            'invoice_id',
            'run_registration_type',
            'type',
            'invoicing_company_name',
            'invoicing_address',
            'invoicing_address_extension',
            'invoicing_postal_code',
            'invoicing_locality',
            'invoicing_email',
            'invoicing_note',
            'payment_iban',
            'payment_note',
            'company_name',
            'company_bloc',
            'school_name',
            'school_postal_code',
            'school_locality',
            'school_country',
            'school_class_level',
            'school_class_holder_first_name',
            'school_class_holder_last_name',
            'school_class_holder_email',
            'school_class_holder_phone',
            'contact_first_name',
            'contact_last_name',
            'contact_email',
            'contact_phone',
        ];

        $runRegistration = new RunRegistration;

        $expected = $fillable;
        $actual = $runRegistration->getFillable();
        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $runRegistration = new RunRegistration;
        $casts = $runRegistration->getCasts();

        $this->assertEquals(RunRegistrationType::class, $casts['run_registration_type']);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(RunRegistration::class)));
    }

    /** @test */
    public function it_determines_display_name_correctly_based_on_type()
    {
        $regSchool = new RunRegistration([
            'run_registration_type' => RunRegistrationType::School,
            'school_name'           => 'Collège des Creusets',
            'contact_first_name'    => 'Isabelle',
            'contact_last_name'     => 'Emery',
        ]);
        $this->assertEquals('Collège des Creusets', $regSchool->display_name);

        $regCompany = new RunRegistration([
            'run_registration_type' => RunRegistrationType::Company,
            'company_name'          => 'UBS Valais',
        ]);
        $this->assertEquals('UBS Valais', $regCompany->display_name);

        $regGroup = new RunRegistration([
            'run_registration_type' => RunRegistrationType::Group,
            'contact_first_name'    => 'Antoine',
            'contact_last_name'     => 'Clivaz',
        ]);
        $this->assertEquals('Antoine Clivaz', $regGroup->display_name);
    }

    /** @test */
    public function it_cascades_route_notification_for_mail_properly()
    {
        Edition::factory()->create(['year' => (int) date('Y')]);

        $client = Client::factory()->create(['email' => 'client@acme.ch']);

        // Case 1: contact_email set
        $reg1 = new RunRegistration([
            'contact_email'             => 'contact@acme.ch',
            'school_class_holder_email' => 'holder@acme.ch',
            'invoicing_email'           => 'invoice@acme.ch',
            'client_id'                 => $client->id,
        ]);
        $reg1->setRelation('client', $client);
        $this->assertEquals('contact@acme.ch', $reg1->routeNotificationForMail());

        // Case 2: contact_email empty, holder_email set
        $reg2 = new RunRegistration([
            'school_class_holder_email' => 'holder@acme.ch',
            'invoicing_email'           => 'invoice@acme.ch',
            'client_id'                 => $client->id,
        ]);
        $reg2->setRelation('client', $client);
        $this->assertEquals('holder@acme.ch', $reg2->routeNotificationForMail());

        // Case 3: only client_id email set
        $reg3 = new RunRegistration([
            'client_id' => $client->id,
        ]);
        $reg3->setRelation('client', $client);
        $this->assertEquals('client@acme.ch', $reg3->routeNotificationForMail());
    }

    /** @test */
    public function it_calculates_participants_count_and_estimated_total_correctly()
    {
        $run10k = Run::factory()->create(['cost' => 35.00]);

        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::Group,
            'contact_first_name'    => 'Marc',
            'contact_last_name'     => 'Dubuis',
        ]);

        // Participant 1: Normal paid
        RunRegistrationElement::create([
            'run_registration_id'       => $reg->id,
            'first_name'                => 'Jean',
            'last_name'                 => 'Favre',
            'run_id'                    => $run10k->id,
            'has_free_registration_fee' => false,
        ]);

        // Participant 2: Free voucher
        RunRegistrationElement::create([
            'run_registration_id'       => $reg->id,
            'first_name'                => 'Sophie',
            'last_name'                 => 'Martin',
            'run_id'                    => $run10k->id,
            'has_free_registration_fee' => true,
        ]);

        $this->assertEquals(2, $reg->participants_count);
        $this->assertEquals(35.00, $reg->calculateEstimatedTotal());
    }
}
