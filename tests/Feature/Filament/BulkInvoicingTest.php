<?php

namespace Tests\Feature\Filament;

use App\Models\Run;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Provision;
use App\Models\RunRegistration;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use App\Services\InvoiceService;
use App\Models\RunRegistrationElement;
use App\Notifications\ClientSendInvoice;
use App\Services\RunRegistrationService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ClientSendInvoiceRelaunch;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\RunRegistrationResource\Pages\ListRunRegistrations;

class BulkInvoicingTest extends TestCase
{
    use RefreshDatabase;

    private Edition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $this->edition->id]);
        config(['cdn.default_edition_id' => $this->edition->id]);
        InvoiceService::flushCache();
        $this->actingAs(User::factory()->create());
    }

    private function clientWithProvision(int $count = 1): Client
    {
        $client = Client::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            ProvisionElement::factory()->create([
                'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
                'provision_id' => Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null])->id,
                'has_product'  => true, 'quantity' => 1, 'cost' => 100, 'tax_rate' => 8.1,
            ]);
        }

        return $client;
    }

    /** Le quota « inscriptions incluses » dérive des prestations de vouchers CDN du client. */
    private function giveQuota(Client $client, int $quota): void
    {
        $provision = Provision::factory()->create(['edition_id' => $this->edition->id, 'product_id' => null]);
        setting(['voucher_cdn_provisions' => [$provision->id]]);
        ProvisionElement::factory()->create([
            'edition_id'   => $this->edition->id, 'recipient_id' => $client->id, 'recipient_type' => Client::class,
            'provision_id' => $provision->id, 'numeric_indicator' => $quota, 'has_product' => false,
        ]);
    }

    private function lastNotification(): array
    {
        $component = new Notifications;
        $component->mount();

        return $component->notifications->first()->toArray();
    }

    private function invoice(Client $client, InvoiceStatusEnum $status, array $extra = []): Invoice
    {
        return Invoice::create(array_merge([
            'edition_id' => $this->edition->id, 'client_id' => $client->id, 'status' => $status,
            'number'     => 'T'.fake()->unique()->numerify('####'), 'date' => now(),
            'positions'  => [['name' => 'X', 'quantity' => 1, 'cost' => 10, 'tax_rate' => 8.1]],
        ], $extra));
    }

    private function registration(Client $client, int $participants = 1, ?\Closure $state = null): RunRegistration
    {
        $factory = RunRegistration::factory();
        $registration = ($state ? $state($factory) : $factory)->create(['client_id' => $client->id]);
        $run = Run::factory()->create(['cost' => 50]);
        RunRegistrationElement::factory()->count($participants)->create([
            'run_registration_id' => $registration->id, 'run_id' => $run->id, 'has_free_registration_fee' => false,
        ]);

        return $registration;
    }

    // ───────────── Prestations : aperçu et rapport ─────────────

    public function test_provisions_bulk_preview_lists_each_client_with_what_will_happen(): void
    {
        $todo = $this->clientWithProvision(2);
        $done = $this->clientWithProvision();
        InvoiceService::generateInvoiceByClient($done->id);

        Livewire::test(ListClients::class)
            ->mountTableBulkAction('generateInvoices', [$todo, $done])
            ->assertMountedActionModalSee($todo->name)
            ->assertMountedActionModalSee('Nouvelle facture · 2 prestation(s)')
            ->assertMountedActionModalSee($done->name)
            ->assertMountedActionModalSee('Rien à facturer (tout est déjà facturé)')
            ->assertMountedActionModalSee('1 nouvelle(s) facture(s)');
    }

    public function test_provisions_bulk_only_processes_checked_clients_and_reports(): void
    {
        $a = $this->clientWithProvision();
        $b = $this->clientWithProvision();
        $done = $this->clientWithProvision();
        InvoiceService::generateInvoiceByClient($done->id);

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$a, $b, $done], data: ['client_ids' => [$a->id]])
            ->assertNotified();

        $this->assertEquals(1, Invoice::where('client_id', $a->id)->count());
        $this->assertEquals(0, Invoice::where('client_id', $b->id)->count(), 'client décoché');
        $this->assertEquals(1, Invoice::where('client_id', $done->id)->count(), 'pas de doublon');
    }

    public function test_bulk_report_is_persistent_and_offers_undo_for_created_invoices(): void
    {
        $a = $this->clientWithProvision();

        Livewire::test(ListClients::class)
            ->callTableBulkAction('generateInvoices', [$a], data: ['client_ids' => [$a->id]]);

        $notification = $this->lastNotification();
        $this->assertTrue($notification['duration'] === 'persistent');
        $this->assertStringContainsString('1 facture(s) créée(s)', $notification['title']);
        $this->assertStringContainsString($a->name, $notification['body']);
        $this->assertSame('undo', $notification['actions'][0]['name']);
        $this->assertSame('undo-bulk-invoices', $notification['actions'][0]['event']);
        $this->assertSame(
            [Invoice::firstOrFail()->id],
            $notification['actions'][0]['eventData']['invoiceIds'],
        );
    }

    public function test_undo_cancels_unsent_invoices_and_frees_their_provisions(): void
    {
        $a = $this->clientWithProvision();
        $b = $this->clientWithProvision();
        $invoiceA = InvoiceService::generateInvoiceByClient($a->id);
        $invoiceB = InvoiceService::generateInvoiceByClient($b->id);
        $invoiceB->update(['status' => InvoiceStatusEnum::Sent]); // déjà envoyée entre-temps

        Livewire::test(ListClients::class)
            ->call('undoBulkInvoices', [$invoiceA->id, $invoiceB->id])
            ->assertNotified();

        $this->assertEquals(InvoiceStatusEnum::Cancelled, $invoiceA->fresh()->status);
        $this->assertEquals(InvoiceStatusEnum::Sent, $invoiceB->fresh()->status, 'une facture envoyée n\'est jamais annulée');
        $this->assertCount(1, InvoiceService::uninvoicedProvisionElements($a->id));
        $this->assertCount(0, InvoiceService::uninvoicedProvisionElements($b->id));
    }

    // ───────────── Courses : aperçu, périmètre, Élite, quota ─────────────

    public function test_registrations_bulk_preview_says_the_whole_client_is_invoiced(): void
    {
        $client = Client::factory()->create();
        $first = $this->registration($client, 2);
        $this->registration($client, 2);

        Livewire::test(ListRunRegistrations::class)
            ->mountTableBulkAction('generateInvoices', [$first])
            ->assertMountedActionModalSee($client->name)
            ->assertMountedActionModalSee('4 inscription(s) (2 dans votre sélection)');
    }

    public function test_registrations_bulk_ignores_registrations_without_client_and_says_so(): void
    {
        $withClient = $this->registration(Client::factory()->create());
        $orphan = RunRegistration::factory()->create(['client_id' => null]);

        Livewire::test(ListRunRegistrations::class)
            ->mountTableBulkAction('generateInvoices', [$withClient, $orphan])
            ->assertMountedActionModalSee('1 inscription(s) sans client ignorée(s)');
    }

    public function test_elite_registrations_are_never_invoiced_automatically(): void
    {
        $client = Client::factory()->create();
        $this->registration($client, 1, fn ($f) => $f->elite());

        $plan = app(RunRegistrationService::class)->plan($client->id);
        $this->assertSame(1, $plan['elite_excluded']);
        $this->assertTrue($plan['elements']->isEmpty());

        $this->expectExceptionMessage('Élite');
        app(RunRegistrationService::class)->createInvoiceForClient($client->id);
    }

    public function test_elite_next_to_regular_registrations_is_excluded_with_a_warning(): void
    {
        $client = Client::factory()->create();
        $this->registration($client, 2);
        $this->registration($client, 3, fn ($f) => $f->elite());

        $plan = app(RunRegistrationService::class)->plan($client->id);

        $this->assertCount(2, $plan['elements']);
        $this->assertSame(3, $plan['elite_excluded']);
        $this->assertStringContainsString('3 inscription(s) Élite non facturée(s)', implode(' ', $plan['warnings']));
        $this->assertSame(2, (int) app(RunRegistrationService::class)->createInvoiceForClient($client->id)->positions[0]['quantity']);
    }

    public function test_plan_writes_nothing_and_quota_is_only_applied_on_generation(): void
    {
        $client = Client::factory()->create();
        $this->giveQuota($client, 2);
        $this->registration($client, 3);

        $service = app(RunRegistrationService::class);
        $plan = $service->plan($client->id);

        $this->assertCount(2, $plan['quota_ids']);
        $this->assertSame(0, RunRegistrationElement::where('has_free_registration_fee', true)->count(), 'la simulation ne modifie rien');
        $this->assertSame(0, Invoice::count());

        $service->createInvoiceForClient($client->id);

        $this->assertSame(2, RunRegistrationElement::where('has_free_registration_fee', true)->count());
    }

    public function test_quota_never_flips_registrations_that_are_already_invoiced(): void
    {
        $client = Client::factory()->create();
        $this->giveQuota($client, 1);
        $registration = $this->registration($client, 2);
        $service = app(RunRegistrationService::class);

        $first = $service->createInvoiceForClient($client->id);
        $first->update(['status' => InvoiceStatusEnum::Sent]);
        $paidBefore = RunRegistrationElement::where('has_free_registration_fee', false)->pluck('id')->all();

        RunRegistrationElement::factory()->create(['run_registration_id' => $registration->id, 'run_id' => Run::first()->id, 'has_free_registration_fee' => false]);
        $service->createInvoiceForClient($client->id);

        $this->assertSame($paidBefore, RunRegistrationElement::whereIn('id', $paidBefore)->where('has_free_registration_fee', false)->pluck('id')->all());
    }

    public function test_generation_is_atomic_when_something_fails(): void
    {
        $client = Client::factory()->create();
        $this->giveQuota($client, 1);
        $this->registration($client, 2);

        // Force un échec pendant la création de la facture
        Invoice::creating(fn () => throw new \RuntimeException('boom'));

        try {
            app(RunRegistrationService::class)->createInvoiceForClient($client->id);
            $this->fail('exception attendue');
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, RunRegistrationElement::where('has_free_registration_fee', true)->count(), 'le quota est annulé avec la facture');
        $this->assertSame(0, Invoice::count());
    }

    public function test_registrations_bulk_report_details_created_and_skipped(): void
    {
        $ok = Client::factory()->create();
        $this->registration($ok);
        $done = Client::factory()->create();
        $doneRegistration = $this->registration($done);
        app(RunRegistrationService::class)->createInvoiceForClient($done->id)->update(['status' => InvoiceStatusEnum::Sent]);

        Livewire::test(ListRunRegistrations::class)
            ->callTableBulkAction('generateInvoices', RunRegistration::all(), data: ['client_ids' => [$ok->id]]);

        $notification = $this->lastNotification();
        $this->assertStringContainsString('1 facture(s) créée(s)', $notification['title']);
        $this->assertStringContainsString($ok->name, $notification['body']);
        $this->assertEquals(1, Invoice::where('client_id', $done->id)->count(), 'pas de doublon pour le client déjà facturé');
    }

    // ───────────── Envoi / relance : éligibilité ─────────────

    public function test_send_preview_marks_ineligible_invoices_with_a_reason(): void
    {
        $ready = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready);
        $paid = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Paid);
        $proforma = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready, ['is_pro_forma' => true]);

        Livewire::test(ListInvoices::class)
            ->mountTableBulkAction('sendInvoices', [$ready, $paid, $proforma])
            ->assertMountedActionModalSee('1 facture(s) peuvent être envoyées · 2 ignorée(s)')
            ->assertMountedActionModalSee('Ignorée : statut')
            ->assertMountedActionModalSee('Ignorée : facture proforma');
    }

    public function test_bulk_send_never_touches_paid_or_proforma_or_unchecked_invoices(): void
    {
        Notification::fake();
        $ready = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready);
        $paid = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Paid);
        $proforma = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready, ['is_pro_forma' => true]);
        $unchecked = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready);

        Livewire::test(ListInvoices::class)
            ->callTableBulkAction('sendInvoices', [$ready, $paid, $proforma, $unchecked], data: ['record_ids' => [$ready->id]]);

        Notification::assertSentTo($ready->client, ClientSendInvoice::class);
        Notification::assertNotSentTo($paid->client, ClientSendInvoice::class);
        Notification::assertNotSentTo($proforma->client, ClientSendInvoice::class);
        Notification::assertNotSentTo($unchecked->client, ClientSendInvoice::class);
        $this->assertEquals(InvoiceStatusEnum::Sent, $ready->fresh()->status);
        $this->assertEquals(InvoiceStatusEnum::Paid, $paid->fresh()->status, 'une facture payée ne redevient jamais envoyée');
        $this->assertEquals(InvoiceStatusEnum::Ready, $unchecked->fresh()->status);
    }

    public function test_bulk_relaunch_only_targets_sent_unpaid_invoices(): void
    {
        Notification::fake();
        $sent = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Sent);
        $ready = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready);
        $paid = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Paid);

        Livewire::test(ListInvoices::class)
            ->callTableBulkAction('relaunchInvoices', [$sent, $ready, $paid], data: ['record_ids' => [$sent->id]]);

        Notification::assertSentTo($sent->client, ClientSendInvoiceRelaunch::class);
        Notification::assertNotSentTo($ready->client, ClientSendInvoiceRelaunch::class);
        Notification::assertNotSentTo($paid->client, ClientSendInvoiceRelaunch::class);
        $this->assertEquals(InvoiceStatusEnum::Relaunched, $sent->fresh()->status);
        $this->assertEquals(InvoiceStatusEnum::Paid, $paid->fresh()->status);
    }

    public function test_invoice_without_billing_email_is_not_sent(): void
    {
        Notification::fake();
        $client = Client::factory()->create(['email' => null, 'invoicing_email' => null]);
        $invoice = $this->invoice($client, InvoiceStatusEnum::Ready);

        Livewire::test(ListInvoices::class)
            ->mountTableBulkAction('sendInvoices', [$invoice])
            ->assertMountedActionModalSee('pas d\'e-mail de facturation');
    }

    public function test_row_actions_are_hidden_when_the_status_does_not_allow_them(): void
    {
        $paid = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Paid);
        $ready = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready);
        $sent = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Sent);

        Livewire::test(ListInvoices::class)
            ->assertTableActionHidden('sendInvoice', $paid)
            ->assertTableActionHidden('relaunchInvoice', $paid)
            ->assertTableActionVisible('sendInvoice', $ready)
            ->assertTableActionHidden('relaunchInvoice', $ready)
            ->assertTableActionHidden('sendInvoice', $sent)
            ->assertTableActionVisible('relaunchInvoice', $sent);
    }

    public function test_row_send_confirmation_names_the_exact_invoice_and_recipient(): void
    {
        $invoice = $this->invoice(Client::factory()->create(['email' => 'facturation@exemple.ch']), InvoiceStatusEnum::Ready);

        Livewire::test(ListInvoices::class)
            ->mountTableAction('sendInvoice', $invoice)
            ->assertMountedActionModalSee($invoice->number)
            ->assertMountedActionModalSee('facturation@exemple.ch');
    }

    public function test_latest_invoice_of_a_client_ignores_pro_forma_and_cancelled(): void
    {
        $client = Client::factory()->create();
        $real = $this->invoice($client, InvoiceStatusEnum::Sent);
        $this->invoice($client, InvoiceStatusEnum::Ready, ['is_pro_forma' => true]);
        $this->invoice($client, InvoiceStatusEnum::Cancelled);

        $this->assertEquals($real->id, $client->latestInvoice()->id);
    }

    public function test_client_list_bulk_send_previews_the_exact_invoice_of_each_client(): void
    {
        $client = Client::factory()->create();
        $invoice = $this->invoice($client, InvoiceStatusEnum::Ready);
        $noInvoice = Client::factory()->create();

        Livewire::test(ListClients::class)
            ->mountTableBulkAction('sendInvoices', [$client, $noInvoice])
            ->assertMountedActionModalSee('facture '.$invoice->number)
            ->assertMountedActionModalSee('Ignorée : aucune facture');
    }

    // ───────────── Libellé des factures d'inscriptions ─────────────

    public function test_new_registration_invoice_has_a_clean_title_without_dossier_numbers(): void
    {
        $client = Client::factory()->create(['name' => 'Banque Test']);
        $this->registration($client);
        $this->registration($client);

        $invoice = app(RunRegistrationService::class)->createInvoiceForClient($client->id);

        $this->assertSame('Facture d\'inscriptions - Banque Test', $invoice->title);
        $this->assertSame('Facture d\'inscriptions', $invoice->note);
        $this->assertStringNotContainsString('Dossier', $invoice->title.$invoice->note);
        $this->assertSame('run_registrations', $invoice->meta['source']);
        $this->assertSame('courses', $invoice->type());
    }

    public function test_qr_additional_information_uses_the_clean_title(): void
    {
        $client = Client::factory()->create(['name' => 'Banque Test']);
        $this->registration($client);
        $invoice = app(RunRegistrationService::class)->createInvoiceForClient($client->id);

        $qrBill = InvoiceService::generateQrBill($client, $invoice);

        $this->assertSame('Facture d\'inscriptions - Banque Test', $qrBill->getAdditionalInformation()->getUnstructuredMessage());
    }

    public function test_legacy_titles_are_cleaned_at_display_time_without_touching_the_database(): void
    {
        $client = Client::factory()->create();
        $legacy = $this->invoice($client, InvoiceStatusEnum::Sent, [
            'title' => 'Facture Inscriptions Courses - Mon Client (Dossiers #1, #4, #5, #6, #16, #19)',
            'note'  => 'Facture Inscriptions Courses regroupant les dossiers #1, #4',
        ]);

        $this->assertSame('Facture d\'inscriptions - Mon Client', $legacy->title);
        $this->assertSame(
            'Facture Inscriptions Courses - Mon Client (Dossiers #1, #4, #5, #6, #16, #19)',
            \DB::table('invoices')->where('id', $legacy->id)->value('title'),
            'les données en base ne sont pas modifiées',
        );
        $this->assertSame('courses', $legacy->type(), 'l\'ancienne facture reste reconnue comme facture de courses');
    }

    public function test_titles_without_dossier_mention_are_left_untouched(): void
    {
        $invoice = $this->invoice(Client::factory()->create(), InvoiceStatusEnum::Ready, ['title' => 'Facture 2025012 (acompte #2)']);

        $this->assertSame('Facture 2025012 (acompte #2)', $invoice->title);
    }
}
