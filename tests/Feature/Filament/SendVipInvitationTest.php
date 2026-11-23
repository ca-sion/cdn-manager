<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ProvisionElement;
use Filament\Actions\Testing\TestAction;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RecipientSendVipInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;

class SendVipInvitationTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    private Provision $vipProvision;

    private Provision $otherProvision;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);

        $this->vipProvision = Provision::factory()->create(['product_id' => null, 'has_vip' => true]);
        $this->otherProvision = Provision::factory()->create(['product_id' => null, 'has_vip' => false]);

        setting(['vip_provision' => (string) $this->vipProvision->id]);

        $this->actingAs(User::factory()->create());
    }

    public function test_provision_elements_bulk_vip_preselects_ready_skips_already_sent_and_disables_ineligible(): void
    {
        Notification::fake();

        $readyContact = Contact::factory()->create(['email' => 'ready@example.test']);
        $sentContact = Contact::factory()->create(['email' => 'sent@example.test']);
        $answeredContact = Contact::factory()->create(['email' => 'answered@example.test']);
        $noEmailContact = Contact::factory()->create(['email' => null]);

        $ready = ProvisionElement::create([
            'edition_id'            => $this->edition->id,
            'provision_id'          => $this->vipProvision->id,
            'recipient_type'        => Contact::class,
            'recipient_id'          => $readyContact->id,
            'status'                => ProvisionElementStatusEnum::ToPrepare,
            'vip_category'          => 'Autorité',
            'vip_invitation_number' => 2,
        ]);

        $alreadySent = ProvisionElement::create([
            'edition_id'     => $this->edition->id,
            'provision_id'   => $this->vipProvision->id,
            'recipient_type' => Contact::class,
            'recipient_id'   => $sentContact->id,
            'status'         => ProvisionElementStatusEnum::Sent,
        ]);

        $alreadyAnswered = ProvisionElement::create([
            'edition_id'          => $this->edition->id,
            'provision_id'        => $this->vipProvision->id,
            'recipient_type'      => Contact::class,
            'recipient_id'        => $answeredContact->id,
            'status'              => ProvisionElementStatusEnum::Sent,
            'vip_response_status' => 1,
        ]);

        $noEmail = ProvisionElement::create([
            'edition_id'     => $this->edition->id,
            'provision_id'   => $this->vipProvision->id,
            'recipient_type' => Contact::class,
            'recipient_id'   => $noEmailContact->id,
            'status'         => ProvisionElementStatusEnum::ToPrepare,
        ]);

        $notVip = ProvisionElement::create([
            'edition_id'     => $this->edition->id,
            'provision_id'   => $this->otherProvision->id,
            'recipient_type' => Contact::class,
            'recipient_id'   => $readyContact->id,
            'status'         => ProvisionElementStatusEnum::ToPrepare,
        ]);

        Livewire::test(ListProvisionElements::class)
            ->selectTableRecords([$ready->id, $alreadySent->id, $alreadyAnswered->id, $noEmail->id, $notVip->id])
            ->mountAction(TestAction::make('sendVipInvitation')->table()->bulk())
            ->assertSchemaStateSet([
                'record_ids' => [$ready->id],
            ])
            ->callMountedAction();

        Notification::assertSentTo($readyContact, RecipientSendVipInvitation::class);
        Notification::assertNotSentTo($sentContact, RecipientSendVipInvitation::class);
        Notification::assertNotSentTo($answeredContact, RecipientSendVipInvitation::class);
        Notification::assertNotSentTo($noEmailContact, RecipientSendVipInvitation::class);

        $this->assertSame(ProvisionElementStatusEnum::Sent, $ready->fresh()->status);
    }

    public function test_clients_bulk_vip_sends_only_to_checked_clients_with_vip_provision(): void
    {
        Notification::fake();

        $eligible = Client::factory()->create(['email' => 'client@example.test']);
        ProvisionElement::create([
            'edition_id'     => $this->edition->id,
            'provision_id'   => $this->vipProvision->id,
            'recipient_type' => Client::class,
            'recipient_id'   => $eligible->id,
            'status'         => ProvisionElementStatusEnum::ToPrepare,
        ]);

        $withoutVip = Client::factory()->create(['email' => 'other@example.test']);

        Livewire::test(ListClients::class)
            ->selectTableRecords([$eligible->id, $withoutVip->id])
            ->mountAction(TestAction::make('sendVipInvitation')->table()->bulk())
            ->assertSchemaStateSet([
                'record_ids' => [$eligible->id],
            ])
            ->callMountedAction();

        Notification::assertSentTo($eligible, RecipientSendVipInvitation::class);
        Notification::assertNotSentTo($withoutVip, RecipientSendVipInvitation::class);
    }
}
