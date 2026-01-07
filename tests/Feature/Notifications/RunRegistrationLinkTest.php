<?php

namespace Tests\Feature\Notifications;

use App\Models\RunRegistration;
use App\Notifications\RunRegistrationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RunRegistrationLinkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_send_registration_link_notification()
    {
        Notification::fake();

        $registration = RunRegistration::factory()->create([
            'contact_email' => 'test@example.com',
            'contact_first_name' => 'Michael'
        ]);

        $registration->notify(new RunRegistrationLink());

        Notification::assertSentTo(
            $registration,
            RunRegistrationLink::class
        );
    }
}
