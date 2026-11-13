<?php

namespace Tests\Feature\Filament;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use App\Filament\Actions\Support\ClientMailing;
use App\Notifications\ClientAdvertiserFormLink;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\ClientAdvertiserFormRelaunch;
use App\Notifications\ClientInterclassDonorRequest;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Notifications\ClientInterclassDonorRequestRelaunch;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ClientMailingTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);
        $this->actingAs(User::factory()->create());
    }

    private function client(array $attributes = [], ?EngagementStageEnum $stage = null, ?Carbon $sentAt = null): Client
    {
        $client = Client::factory()->create($attributes);
        ClientEngagement::updateOrCreate(
            ['client_id' => $client->id, 'edition_id' => $this->edition->id],
            ['stage' => $stage, 'status' => null, 'sent_at' => $sentAt],
        );

        return $client->fresh();
    }

    private function lastNotification(): array
    {
        $component = new Notifications;
        $component->mount();

        return $component->notifications->first()->toArray();
    }

    private function engagement(Client $client): ClientEngagement
    {
        return ClientEngagement::withoutGlobalScopes()->where('client_id', $client->id)->where('edition_id', $this->edition->id)->firstOrFail();
    }

    // ───────────── Règles d'éligibilité ─────────────

    public function test_client_without_email_cannot_be_contacted(): void
    {
        $client = $this->client(['email' => null]);

        $assessment = ClientMailing::assess('send_advertiser_form', $client);

        $this->assertSame('pas d\'e-mail', $assessment['disabled']);
        $this->assertFalse($assessment['checked']);
    }

    public function test_first_send_to_an_already_contacted_client_is_unchecked_with_a_warning(): void
    {
        $client = $this->client([], EngagementStageEnum::ProposalSent, now()->setDate(2026, 11, 12)->setTime(10, 0));

        $assessment = ClientMailing::assess('send_advertiser_form', $client);

        $this->assertNull($assessment['disabled'], 'pas bloqué');
        $this->assertFalse($assessment['checked'], 'décoché par défaut');
        $this->assertStringContainsString('déjà contacté le 12.11.2026', $assessment['warning']);
    }

    public function test_first_send_to_a_fresh_client_is_checked(): void
    {
        $assessment = ClientMailing::assess('send_interclass_donors_email', $this->client());

        $this->assertTrue($assessment['checked']);
        $this->assertNull($assessment['warning']);
    }

    public function test_relaunch_only_for_contacted_clients_still_at_the_sent_stage(): void
    {
        $neverContacted = $this->client([], EngagementStageEnum::ProposalSent, null);
        $confirmed = $this->client([], EngagementStageEnum::Confirmed, now()->subDay());
        $waiting = $this->client([], EngagementStageEnum::ProposalSent, now()->subDay());

        $this->assertStringContainsString('jamais contacté', ClientMailing::assess('relaunch_advertiser_form', $neverContacted)['disabled']);
        $this->assertStringContainsString('« 3. Confirmé »', ClientMailing::assess('relaunch_advertiser_form', $confirmed)['disabled']);
        $this->assertNull(ClientMailing::assess('relaunch_advertiser_form', $waiting)['disabled']);
        $this->assertTrue(ClientMailing::assess('relaunch_advertiser_form', $waiting)['checked']);
    }

    public function test_client_without_previous_order_stays_checked_with_an_information(): void
    {
        $assessment = ClientMailing::assess('send_advertiser_form', $this->client());

        $this->assertTrue($assessment['checked']);
        $this->assertStringContainsString('pas de commande l\'an dernier', $assessment['info']);
    }

    // ───────────── Envoi et suivi ─────────────

    public function test_each_mailing_sends_its_own_notification_and_updates_the_follow_up(): void
    {
        Notification::fake();

        $map = [
            'send_advertiser_form'             => [ClientAdvertiserFormLink::class, EngagementStatusEnum::Idle, 'sent_at'],
            'send_interclass_donors_email'     => [ClientInterclassDonorRequest::class, EngagementStatusEnum::Idle, 'sent_at'],
            'relaunch_advertiser_form'         => [ClientAdvertiserFormRelaunch::class, EngagementStatusEnum::Relaunched, 'relaunched_at'],
            'relaunch_interclass_donors_email' => [ClientInterclassDonorRequestRelaunch::class, EngagementStatusEnum::Relaunched, 'relaunched_at'],
        ];

        foreach ($map as $key => [$notification, $status, $dateColumn]) {
            $client = $this->client([], EngagementStageEnum::ProposalSent, str_starts_with($key, 'relaunch') ? now()->subDay() : null);

            ClientMailing::send($key, $client);

            Notification::assertSentTo($client, $notification);
            $engagement = $this->engagement($client);
            $this->assertSame(EngagementStageEnum::ProposalSent, $engagement->stage, $key);
            $this->assertSame($status, $engagement->status, $key);
            $this->assertNotNull($engagement->{$dateColumn}, $key);
        }
    }

    // ───────────── En masse : aperçu, envoi, rapport ─────────────

    public function test_bulk_preview_lists_recipient_dates_and_reasons(): void
    {
        $fresh = $this->client(['email' => 'nouveau@exemple.ch']);
        $already = $this->client([], EngagementStageEnum::ProposalSent, now()->setDate(2026, 11, 12));
        $noEmail = $this->client(['email' => null]);

        Livewire::test(ListClients::class)
            ->mountTableBulkAction('send_advertiser_form', [$fresh, $already, $noEmail])
            ->assertMountedActionModalSee($fresh->name)
            ->assertMountedActionModalSee('nouveau@exemple.ch')
            ->assertMountedActionModalSee('déjà contacté le 12.11.2026')
            ->assertMountedActionModalSee('Ignoré : pas d\'e-mail')
            ->assertMountedActionModalSee('1 à contacter · 1 déjà contacté(s) (décoché(s)) · 1 ignoré(s)')
            ->assertMountedActionModalSee('ne peut pas être annulé');
    }

    public function test_bulk_sends_only_checked_clients_and_never_ineligible_ones(): void
    {
        Notification::fake();
        $checked = $this->client();
        $unchecked = $this->client();
        $already = $this->client([], EngagementStageEnum::ProposalSent, now()->subDays(3));
        $noEmail = $this->client(['email' => null]);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('send_advertiser_form', [$checked, $unchecked, $already, $noEmail], data: ['client_ids' => [$checked->id, $already->id]]);

        Notification::assertSentTo($checked, ClientAdvertiserFormLink::class);
        Notification::assertSentTo($already, ClientAdvertiserFormLink::class, fn () => true); // recoché volontairement
        Notification::assertNotSentTo($unchecked, ClientAdvertiserFormLink::class);
        Notification::assertNotSentTo($noEmail, ClientAdvertiserFormLink::class);

        $notification = $this->lastNotification();
        $this->assertSame('persistent', $notification['duration']);
        $this->assertStringContainsString('2 email(s) envoyé(s)', $notification['title']);
        $this->assertStringContainsString('2 ignoré(s)', $notification['title']);
        $this->assertStringContainsString('non coché', $notification['body']);
        $this->assertEmpty($notification['actions'] ?? [], 'pas d\'annulation possible pour un email');
    }

    public function test_bulk_relaunch_skips_clients_who_already_answered(): void
    {
        Notification::fake();
        $waiting = $this->client([], EngagementStageEnum::ProposalSent, now()->subDays(5));
        $confirmed = $this->client([], EngagementStageEnum::Confirmed, now()->subDays(5));

        Livewire::test(ListClients::class)
            ->mountTableBulkAction('relaunch_advertiser_form', [$waiting, $confirmed])
            ->assertMountedActionModalSee('Ignoré : étape « 3. Confirmé »');

        Livewire::test(ListClients::class)
            ->callTableBulkAction('relaunch_advertiser_form', [$waiting, $confirmed], data: ['client_ids' => [$waiting->id]]);

        Notification::assertSentTo($waiting, ClientAdvertiserFormRelaunch::class);
        Notification::assertNotSentTo($confirmed, ClientAdvertiserFormRelaunch::class);
    }

    public function test_bulk_refuses_a_client_who_became_ineligible_while_the_modal_was_open(): void
    {
        Notification::fake();
        $client = $this->client([], EngagementStageEnum::ProposalSent, now()->subDay());

        // Le client répond (étape « Confirmé ») pendant que la fenêtre est ouverte : la relance n'est plus permise
        $component = Livewire::test(ListClients::class)->mountTableBulkAction('relaunch_advertiser_form', [$client]);
        $this->engagement($client)->update(['stage' => EngagementStageEnum::Confirmed]);
        $component->setTableBulkActionData(['client_ids' => [$client->id]])->callMountedTableBulkAction();

        Notification::assertNotSentTo($client, ClientAdvertiserFormRelaunch::class);
        $this->assertSame(EngagementStageEnum::Confirmed, $this->engagement($client)->stage, 'rien n\'a été modifié non plus');
    }

    public function test_long_selection_lists_only_clients_to_contact(): void
    {
        $toContact = $this->client([], EngagementStageEnum::ProposalSent, now()->subDay());
        $contacted = collect(range(1, 16))->map(fn () => $this->client([], EngagementStageEnum::Confirmed, now()->subDay()));

        Livewire::test(ListClients::class)
            ->mountTableBulkAction('relaunch_advertiser_form', $contacted->push($toContact)->all())
            ->assertMountedActionModalSee('16 client(s) ignorés, non listés')
            ->assertMountedActionModalDontSee($contacted->first()->name);
    }

    // ───────────── Disponibilité sur les autres pages ─────────────

    public function test_actions_exist_on_the_client_row_the_client_page_and_the_provision_pages(): void
    {
        $client = $this->client();
        $element = ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
            'provision_id' => Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null])->id,
        ]);

        $page = Livewire::test(ListClients::class)->assertTableActionExists('mailing_send_advertiser_form', record: $client);
        foreach (array_keys(ClientMailing::DEFINITIONS) as $key) {
            $page->assertTableActionExists('mailing_'.$key, record: $client)->assertTableBulkActionExists($key);
        }

        $editPage = Livewire::test(EditClient::class, ['record' => $client->getKey()]);
        foreach (array_keys(ClientMailing::DEFINITIONS) as $key) {
            $editPage->assertActionExists('mailing_'.$key);
        }

        $list = Livewire::test(ListProvisionElements::class)->assertCanSeeTableRecords([$element]);
        foreach (array_keys(ClientMailing::DEFINITIONS) as $key) {
            $list->assertTableActionExists('mailing_'.$key, record: $element)->assertTableBulkActionExists($key);
        }

        $rm = Livewire::test(ProvisionElementsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class]);
        foreach (array_keys(ClientMailing::DEFINITIONS) as $key) {
            $rm->assertActionExists(TestAction::make('mailing_'.$key)->table());
        }
    }

    public function test_provision_page_bulk_contacts_each_client_once(): void
    {
        Notification::fake();
        $client = $this->client();
        $elements = collect(range(1, 3))->map(fn () => ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
            'provision_id' => Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null])->id,
        ]));

        Livewire::test(ListProvisionElements::class)
            ->callTableBulkAction('send_advertiser_form', $elements->all(), data: ['client_ids' => [$client->id]]);

        Notification::assertSentToTimes($client, ClientAdvertiserFormLink::class, 1);
    }

    // ───────────── Action individuelle ─────────────

    public function test_single_action_explains_and_sends(): void
    {
        Notification::fake();
        $client = $this->client(['email' => 'direction@exemple.ch']);

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->mountAction('mailing_send_advertiser_form')
            ->assertMountedActionModalSee('direction@exemple.ch')
            ->assertMountedActionModalSee('ne peut pas être annulé')
            ->callMountedAction();

        Notification::assertSentTo($client, ClientAdvertiserFormLink::class);
        $this->assertSame(EngagementStageEnum::ProposalSent, $this->engagement($client)->stage);
    }

    public function test_single_action_requires_confirmation_for_an_already_contacted_client(): void
    {
        Notification::fake();
        $client = $this->client([], EngagementStageEnum::ProposalSent, now()->setDate(2026, 11, 12));

        $component = Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->mountAction('mailing_send_advertiser_form')
            ->assertMountedActionModalSee('déjà contacté le 12.11.2026')
            ->callMountedAction()
            ->assertHasActionErrors(['confirm_again']);

        Notification::assertNotSentTo($client, ClientAdvertiserFormLink::class);

        $component->setActionData(['confirm_again' => true])->callMountedAction();
        Notification::assertSentTo($client, ClientAdvertiserFormLink::class);
    }

    public function test_single_action_refuses_an_ineligible_client(): void
    {
        Notification::fake();
        $client = $this->client([], EngagementStageEnum::Confirmed, now()->subDay());

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->mountAction('mailing_relaunch_advertiser_form')
            ->assertMountedActionModalSee('Impossible')
            ->callMountedAction();

        Notification::assertNotSentTo($client, ClientAdvertiserFormRelaunch::class);
    }
}
