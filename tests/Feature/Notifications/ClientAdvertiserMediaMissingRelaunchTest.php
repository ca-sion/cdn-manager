<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Provision;
use Illuminate\Support\Carbon;
use App\Models\ProvisionElement;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Notifications\ClientAdvertiserMediaMissingRelaunch;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;

class ClientAdvertiserMediaMissingRelaunchTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2026, 'name' => 'Édition 2026']);
        session(['edition_id' => $this->edition->id]);
        $this->actingAs(User::factory()->create());
    }

    private function createProvisionRequiringMedia(): Provision
    {
        return Provision::factory()->create([
            'edition_id'           => $this->edition->id,
            'product_id'           => null,
            'name'                 => 'Banderole stade',
            'description'          => 'Banderole 3m x 1m',
            'dimensions_indicator' => '300 x 100 cm',
            'format_indicator'     => 'PDF vectoriel',
        ]);
    }

    public function test_notification_renders_relaunch_subject_deadline_and_fallback_policy(): void
    {
        Carbon::setTestNow('2026-11-27 09:00:00');

        try {
            $client = Client::factory()->create(['name' => 'Garage du Rhône SA', 'email' => 'pub@garage.ch']);
            $provision = $this->createProvisionRequiringMedia();

            ProvisionElement::create([
                'edition_id'     => $this->edition->id,
                'provision_id'   => $provision->id,
                'recipient_id'   => $client->id,
                'recipient_type' => Client::class,
            ]);

            $mail = (new ClientAdvertiserMediaMissingRelaunch)->toMail($client);

            $this->assertStringContainsString('2026 - Visuel manquant (Garage du Rhône SA) : rappel', $mail->subject);
            $this->assertEquals([['pub@coursedenoel.ch', null]], $mail->replyTo);
            $this->assertEquals([['pub@coursedenoel.ch', null]], $mail->bcc);
            $this->assertEquals('Ajouter les visuels', $mail->actionText);
            $this->assertEquals($client->frontEditLink, $mail->actionUrl);

            $body = implode("\n", $mail->introLines)."\n".implode("\n", $mail->outroLines);
            $this->assertStringContainsString('4 décembre', $body);
            $this->assertStringContainsString('pub@coursedenoel.ch', $body);
            $this->assertStringContainsString('visuel de l\'année passée', $body);
            $this->assertStringContainsString('logo d\'entreprise', $body);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_notification_returns_empty_mail_when_no_media_is_missing(): void
    {
        $client = Client::factory()->create(['name' => 'Client Sans Visuel', 'email' => 'client@example.ch']);

        $mail = (new ClientAdvertiserMediaMissingRelaunch)->toMail($client);

        $this->assertSame('', $mail->subject);
    }

    public function test_filament_exposes_and_executes_media_missing_relaunch_actions(): void
    {
        Notification::fake();

        $provision = $this->createProvisionRequiringMedia();

        $clientWithMissing = Client::factory()->create(['name' => 'Client Avec Manque', 'email' => 'manque@example.ch']);
        $element = ProvisionElement::create([
            'edition_id'     => $this->edition->id,
            'provision_id'   => $provision->id,
            'recipient_id'   => $clientWithMissing->id,
            'recipient_type' => Client::class,
        ]);

        $clientWithoutProvision = Client::factory()->create(['name' => 'Client Sans Prestation', 'email' => 'sans@example.ch']);

        Livewire::test(ListClients::class)
            ->assertTableActionExists('sendMediaMissingRelaunch')
            ->assertTableBulkActionExists('sendMediaMissingRelaunch')
            ->callTableAction('sendMediaMissingRelaunch', $clientWithMissing, data: ['deadline' => '30 novembre'])
            ->assertHasNoTableActionErrors();

        Notification::assertSentTo(
            $clientWithMissing,
            ClientAdvertiserMediaMissingRelaunch::class,
            fn (ClientAdvertiserMediaMissingRelaunch $notification) => $notification->deadline === '30 novembre'
        );

        Notification::fake();

        Livewire::test(ListClients::class)
            ->callTableBulkAction('sendMediaMissingRelaunch', [$clientWithMissing, $clientWithoutProvision], data: ['deadline' => '4 décembre'])
            ->assertHasNoTableBulkActionErrors();

        Notification::assertSentTo($clientWithMissing, ClientAdvertiserMediaMissingRelaunch::class);
        Notification::assertNotSentTo($clientWithoutProvision, ClientAdvertiserMediaMissingRelaunch::class);

        Livewire::test(EditClient::class, ['record' => $clientWithMissing->getRouteKey()])
            ->assertActionExists('sendMediaMissingRelaunch');

        Livewire::test(ListProvisionElements::class)
            ->assertCanSeeTableRecords([$element])
            ->assertTableActionExists('sendMediaMissingRelaunch')
            ->assertTableBulkActionExists('sendMediaMissingRelaunch');
    }
}
