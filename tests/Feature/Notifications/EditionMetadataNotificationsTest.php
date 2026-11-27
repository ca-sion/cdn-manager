<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Run;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Voucher;
use App\Models\ProvisionElement;
use App\Notifications\ClientSendVouchers;
use App\Notifications\ClientAdvertiserFormLink;
use App\Notifications\RecipientSendVipInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\ClientInterclassDonorRequest;
use App\Filament\Resources\EditionResource\Pages\EditEdition;
use App\Filament\Resources\EditionResource\Pages\CreateEdition;

class EditionMetadataNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_edition_metadata_falls_back_to_defaults_and_overrides_notifications(): void
    {
        $edition = Edition::create([
            'name'     => 'Édition 2027',
            'year'     => '2027',
            'metadata' => [
                'event_date'                 => '11 décembre 2027',
                'event_short_date'           => '11 décembre',
                'cdn_edition_number'         => '58ᵉ',
                'trail_edition_number'       => '9ᵉ',
                'expected_participants'      => '6500',
                'advertisers_email'          => 'pub2027@coursedenoel.ch',
                'advertisers_paper_form_url' => 'https://coursedenoel.ch/2027-annonceurs.pdf',
                'interclass_edition_number'  => '27e',
                'interclass_deadline'        => '8 novembre 2027',
                'vip_email'                  => 'vip2027@coursedenoel.ch',
                'vip_start_time'             => '17h00',
                'vip_deadline'               => '3 décembre 2027',
                'vip_invitation_pdf_url'     => 'https://coursedenoel.ch/2027-vip.pdf',
                'vouchers_deadline'          => '21 novembre 2027',
                'vouchers_tshirt_deadline'   => '10 novembre 2027',
            ],
        ]);
        session(['edition_id' => $edition->id]);

        $this->assertSame('11 décembre 2027', $edition->meta('event_date'));
        $this->assertSame(Edition::META_DEFAULTS['advertisers_signature'], $edition->meta('advertisers_signature'));

        $client = Client::factory()->create(['name' => 'Sponsor Test SA']);

        $advertiserMail = (new ClientAdvertiserFormLink($client))->toMail($client);
        $this->assertStringContainsString('2027', $advertiserMail->subject);
        $this->assertEquals([['pub2027@coursedenoel.ch', null]], $advertiserMail->replyTo);
        $advertiserBody = implode("\n", $advertiserMail->introLines)."\n".implode("\n", $advertiserMail->outroLines);
        $this->assertStringContainsString('58ᵉ édition', $advertiserBody);
        $this->assertStringContainsString('11 décembre 2027', $advertiserBody);
        $this->assertStringContainsString('https://coursedenoel.ch/2027-annonceurs.pdf', $advertiserBody);

        $interclassMail = (new ClientInterclassDonorRequest($client))->toMail($client);
        $interclassBody = implode("\n", $interclassMail->introLines)."\n".implode("\n", $interclassMail->outroLines);
        $this->assertStringContainsString('27e édition', $interclassBody);
        $this->assertStringContainsString('8 novembre 2027', $interclassBody);

        $element = ProvisionElement::create([
            'edition_id'     => $edition->id,
            'recipient_id'   => $client->id,
            'recipient_type' => Client::class,
        ]);
        $vipMail = (new RecipientSendVipInvitation($element))->toMail($client);
        $this->assertEquals([['vip2027@coursedenoel.ch', null]], $vipMail->replyTo);
        $vipBody = implode("\n", $vipMail->introLines)."\n".implode("\n", $vipMail->outroLines);
        $this->assertStringContainsString('11 décembre prochain', $vipBody);
        $this->assertStringContainsString('17h00', $vipBody);
        $this->assertStringContainsString('3 décembre 2027', $vipBody);
        $this->assertStringContainsString('https://coursedenoel.ch/2027-vip.pdf', $vipBody);

        $run = Run::factory()->create(['name' => 'Course Entreprises']);
        $voucher = Voucher::create([
            'code'       => 'CDN2027-TEST',
            'client_id'  => $client->id,
            'run_id'     => $run->id,
            'edition_id' => $edition->id,
            'is_used'    => false,
        ]);
        $vouchersMail = (new ClientSendVouchers(collect([$voucher])))->toMail($client);
        $vouchersHtml = collect($vouchersMail->introLines)
            ->map(fn ($line) => is_object($line) && method_exists($line, 'toHtml') ? $line->toHtml() : (string) $line)
            ->implode("\n");
        $this->assertStringContainsString('21 novembre 2027', $vouchersHtml);
        $this->assertStringContainsString('10 novembre 2027', $vouchersHtml);
    }

    public function test_filament_edition_form_saves_metadata(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateEdition::class)
            ->fillForm([
                'name'     => 'Édition 2028',
                'year'     => '2028',
                'metadata' => [
                    'event_date'         => '9 décembre 2028',
                    'cdn_edition_number' => '59ᵉ',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $edition = Edition::where('year', '2028')->firstOrFail();
        $this->assertSame('9 décembre 2028', $edition->meta('event_date'));
        $this->assertSame('59ᵉ', $edition->meta('cdn_edition_number'));

        Livewire::test(EditEdition::class, ['record' => $edition->getRouteKey()])
            ->fillForm([
                'metadata' => [
                    'event_date' => '16 décembre 2028',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('16 décembre 2028', $edition->fresh()->meta('event_date'));
    }

    public function test_empty_metadata_computes_year_defaults_automatically(): void
    {
        $edition2027 = Edition::create([
            'name' => 'Édition 2027',
            'year' => '2027',
        ]);
        session(['edition_id' => $edition2027->id]);

        $this->assertSame('11 décembre 2027', $edition2027->meta('event_date'));
        $this->assertSame('11 décembre', $edition2027->meta('event_short_date'));
        $this->assertSame('58ᵉ', $edition2027->meta('cdn_edition_number'));
        $this->assertSame('9ᵉ', $edition2027->meta('trail_edition_number'));
        $this->assertSame('27e', $edition2027->meta('interclass_edition_number'));
        $this->assertSame('9 novembre 2027', $edition2027->meta('interclass_deadline'));
        $this->assertSame('23 novembre 2027', $edition2027->meta('interclass_relaunch_deadline'));
        $this->assertSame('4 décembre 2027', $edition2027->meta('vip_deadline'));
        $this->assertSame('https://coursedenoel.ch/assets/documents/2027-invitation-vip-simple.pdf', $edition2027->meta('vip_invitation_pdf_url'));
    }
}
