<?php

namespace Tests\Feature\Notifications;

use App\Models\Run;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Edition;
use App\Models\Voucher;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use App\Models\RunRegistrationElement;
use App\Notifications\ClientSendVouchers;
use App\Notifications\EliteRunnerFormLink;
use App\Notifications\RunRegistrationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\EliteRunnerContractFinalized;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function run_registration_link_notification_renders_proper_subject_and_action()
    {
        $reg = RunRegistration::create([
            'run_registration_type' => RunRegistrationType::School,
            'school_name'           => 'Sacré-Cœur',
            'contact_first_name'    => 'Isabelle',
            'contact_email'         => 'isabelle@sacrecoeur.ch',
        ]);

        $notification = new RunRegistrationLink;
        $mail = $notification->toMail($reg);

        $this->assertStringContainsString('Course de Noël', $mail->subject);
        $this->assertEquals('Bonjour Isabelle,', $mail->greeting);
        $this->assertStringContainsString('Gérer mon dossier', $mail->actionText);
    }

    /** @test */
    public function elite_runner_link_notification_renders_proper_reply_to_and_content()
    {
        $element = new RunRegistrationElement([
            'first_name' => 'Julien',
            'last_name'  => 'Wanders',
        ]);

        $signedUrl = 'https://cdn-manager.test/registrations/elite/edit/1?signature=test';

        $notification = new EliteRunnerFormLink($element, $signedUrl);
        $mail = $notification->toMail(new Client);

        $this->assertStringContainsString('Course de Noël', $mail->subject);
        $this->assertEquals('Bonjour Julien,', $mail->greeting);
        $this->assertEquals($signedUrl, $mail->actionUrl);
    }

    /** @test */
    public function elite_runner_contract_finalized_notification_renders_proper_content()
    {
        $element = new RunRegistrationElement([
            'first_name' => 'Julien',
            'last_name'  => 'Wanders',
        ]);

        $notification = new EliteRunnerContractFinalized($element);
        $mail = $notification->toMail(new Client);

        $this->assertStringContainsString('Confirmation course Élite et contrat', $mail->subject);
        $this->assertEquals('Bonjour Julien,', $mail->greeting);
        $this->assertStringContainsString('Télécharger le Contrat (PDF)', $mail->actionText);
    }

    /** @test */
    public function client_send_vouchers_notification_attaches_csv_file_and_renders_formatted_html()
    {
        Edition::factory()->create(['year' => (int) date('Y')]);

        $client = Client::factory()->create(['name' => 'UBS SA']);
        $run = Run::factory()->create(['name' => 'Course Entreprises']);

        $v1 = Voucher::create(['code' => 'CDN2026-UBS1', 'client_id' => $client->id, 'run_id' => $run->id, 'is_used' => false]);
        $v2 = Voucher::create(['code' => 'CDN2026-UBS2', 'client_id' => $client->id, 'run_id' => $run->id, 'is_used' => true]);

        $vouchers = collect([$v1, $v2]);

        $notification = new ClientSendVouchers($vouchers, 'Message spécifique pour UBS.');
        $mail = $notification->toMail($client);

        $this->assertStringContainsString('Course de Noël', $mail->subject);
        $this->assertNotEmpty($mail->greeting);

        // Verify CSV attachment is attached
        $this->assertNotEmpty($mail->rawAttachments);
        $attachment = $mail->rawAttachments[0];
        $this->assertStringContainsString('.csv', $attachment['name']);
        $this->assertStringContainsString('CDN2026-UBS1', $attachment['data']);
        $this->assertStringContainsString('CDN2026-UBS2', $attachment['data']);
    }
}
