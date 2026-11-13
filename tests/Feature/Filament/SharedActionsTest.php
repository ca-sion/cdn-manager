<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Livewire\Livewire;
use App\Models\Contact;
use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ContactResource\Pages\ListContacts;
use App\Filament\Resources\RunRegistrationResource\Pages\ListRunRegistrations;
use App\Filament\Resources\ProvisionElementResource\Pages\ListProvisionElements;

class SharedActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $edition = Edition::create(['year' => 2025, 'name' => '2025 Edition']);
        session(['edition_id' => $edition->id]);
        $this->actingAs(User::factory()->create());
    }

    public function test_client_list_exposes_shared_actions(): void
    {
        $client = Client::factory()->create();

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$client])
            ->assertTableActionExists('clientPdf')
            ->assertTableActionExists('generateInvoice')
            ->assertTableBulkActionExists('copyEmail')
            ->assertTableBulkActionExists('sendVipInvitation')
            ->assertTableBulkActionExists('generateInvoices');
    }

    public function test_contact_list_reuses_copy_email_and_vip_actions(): void
    {
        $contact = Contact::factory()->create();

        Livewire::test(ListContacts::class)
            ->assertCanSeeTableRecords([$contact])
            ->assertTableBulkActionExists('copyEmail')
            ->assertTableBulkActionExists('sendVipInvitation');
    }

    public function test_provision_element_list_exposes_client_pdf_and_bulk_actions(): void
    {
        Livewire::test(ListProvisionElements::class)
            ->assertTableActionExists('clientPdf')
            ->assertTableBulkActionExists('copyEmail')
            ->assertTableBulkActionExists('sendVipInvitation');
    }

    public function test_run_registration_exports_exist_in_header_and_bulk(): void
    {
        Livewire::test(ListRunRegistrations::class)
            ->assertTableActionExists('exportDatasportSchool')
            ->assertTableBulkActionExists('exportDatasportSchool')
            ->assertTableBulkActionExists('exportAggregatedData')
            ->assertTableBulkActionExists('generateInvoices');
    }
}
