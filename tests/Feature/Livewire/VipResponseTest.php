<?php

declare(strict_types=1);

use App\Models\Client;
use Livewire\Livewire;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Product;
use App\Models\Provision;
use App\Livewire\VipResponse;
use App\Models\ProvisionElement;
use Illuminate\Support\Facades\URL;
use App\Notifications\VipResponseUpdated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 403 when accessing vip response without valid signature', function () {
    $edition = Edition::factory()->create();
    $provision = Provision::factory()->create([
        'edition_id' => $edition->id,
        'product_id' => Product::factory(),
    ]);
    $contact = Contact::factory()->create();

    $element = ProvisionElement::factory()->create([
        'edition_id'            => $edition->id,
        'provision_id'          => $provision->id,
        'recipient_type'        => Contact::class,
        'recipient_id'          => $contact->id,
        'vip_invitation_number' => 2,
        'vip_category'          => 'Sponsor',
    ]);

    $this->get(route('vip.response', ['provisionElement' => $element]))
        ->assertForbidden();
});

it('renders the modernized vip response view with a valid signed url', function () {
    $edition = Edition::factory()->create();
    $provision = Provision::factory()->create([
        'edition_id' => $edition->id,
        'product_id' => Product::factory(),
    ]);
    $contact = Contact::factory()->create([
        'first_name' => 'Jean',
        'last_name'  => 'Dupont',
        'email'      => 'jean.dupont@example.ch',
    ]);

    $element = ProvisionElement::factory()->create([
        'edition_id'            => $edition->id,
        'provision_id'          => $provision->id,
        'recipient_type'        => Contact::class,
        'recipient_id'          => $contact->id,
        'vip_invitation_number' => 2,
        'vip_category'          => 'individual',
        'vip_response_status'   => null,
    ]);

    $signedUrl = URL::signedRoute('vip.response', ['provisionElement' => $element]);

    $this->get($signedUrl)
        ->assertOk()
        ->assertSee('Invitation VIP')
        ->assertSee('Course de Noël et Trail des Châteaux')
        ->assertSee('En attente de réponse')
        ->assertSee('Individu');
});

it('saves present response, guests, note and sends confirmation email to contact', function () {
    Notification::fake();

    $edition = Edition::factory()->create();
    $provision = Provision::factory()->create([
        'edition_id' => $edition->id,
        'product_id' => Product::factory(),
    ]);
    $contact = Contact::factory()->create([
        'first_name' => 'Marie',
        'last_name'  => 'Curie',
        'email'      => 'marie.curie@example.ch',
    ]);

    $element = ProvisionElement::factory()->create([
        'edition_id'            => $edition->id,
        'provision_id'          => $provision->id,
        'recipient_type'        => Contact::class,
        'recipient_id'          => $contact->id,
        'vip_invitation_number' => 3,
        'vip_category'          => 'Partenaire',
        'vip_response_status'   => null,
    ]);

    Livewire::test(VipResponse::class, ['provisionElement' => $element])
        ->set('data.vip_response_status', true)
        ->set('data.vip_guests', ['Marie Curie', 'Pierre Curie'])
        ->set('data.note', 'Sans gluten merci')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('confirmationEmail', 'marie.curie@example.ch')
        ->assertSee('Un e-mail de confirmation contenant le récapitulatif de votre réponse');

    $element->refresh();

    expect((bool) $element->vip_response_status)->toBeTrue()
        ->and($element->vip_guests)->toBe(['Marie Curie', 'Pierre Curie'])
        ->and($element->note)->toBe('Sans gluten merci');

    Notification::assertSentTo($contact, VipResponseUpdated::class, function (VipResponseUpdated $notification) use ($contact) {
        $mail = $notification->toMail($contact);
        $rendered = implode("\n", $mail->introLines);

        return str_contains($mail->subject, 'Confirmation de votre réponse VIP')
            && str_contains($rendered, 'Présent')
            && str_contains($rendered, 'Marie Curie, Pierre Curie')
            && str_contains($rendered, 'Sans gluten merci');
    });
});

it('routes confirmation email to client vip contact email and ccs main contact email', function () {
    Notification::fake();

    $edition = Edition::factory()->create();
    $provision = Provision::factory()->create([
        'edition_id' => $edition->id,
        'product_id' => Product::factory(),
    ]);
    $client = Client::factory()->create([
        'name'  => 'Acme SA',
        'email' => 'contact@acme.ch',
    ]);

    $vipContact = Contact::factory()->create([
        'email' => 'vip@acme.ch',
    ]);
    $commercialContact = Contact::factory()->create([
        'email' => 'commercial@acme.ch',
    ]);
    $client->contacts()->attach($vipContact->id, ['type' => 'executive']);
    $client->contacts()->attach($commercialContact->id, ['type' => 'commercial']);

    $element = ProvisionElement::factory()->create([
        'edition_id'            => $edition->id,
        'provision_id'          => $provision->id,
        'recipient_type'        => Client::class,
        'recipient_id'          => $client->id,
        'vip_invitation_number' => 2,
        'vip_category'          => 'Sponsor',
    ]);

    Livewire::test(VipResponse::class, ['provisionElement' => $element])
        ->set('data.vip_response_status', 0)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSet('confirmationEmail', 'vip@acme.ch');

    expect($client->routeNotificationForMail(new VipResponseUpdated($element)))
        ->toBe(['vip@acme.ch' => 'Acme SA']);

    Notification::assertSentTo($client, VipResponseUpdated::class, function (VipResponseUpdated $notification) use ($client) {
        $mail = $notification->toMail($client);
        $rendered = implode("\n", $mail->introLines);

        return str_contains($rendered, 'Absent')
            && collect($mail->cc)->pluck(0)->contains('commercial@acme.ch');
    });
});
