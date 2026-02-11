<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Dicastry;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Models\ContactCategory;
use Illuminate\Database\Seeder;
use App\Enums\InvoiceStatusEnum;
use App\Models\ClientEngagement;
use App\Models\ProvisionElement;
use App\Models\ProvisionCategory;
use Illuminate\Support\Facades\DB;
use App\Enums\ProvisionElementStatusEnum;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        if (! User::where('email', 'info@coursedenoel.ch')->first()) {
            User::factory()->create([
                'name'     => 'Michael',
                'email'    => 'info@coursedenoel.ch',
                'password' => '$2y$12$GF7QBo/M5uYYkmphwfNvNOxbFA.0Aw9xHOtNEwXb8iy.InmOtYKUW',
            ]);
        }

        // 2. Editions
        $edition2024 = Edition::updateOrCreate(['year' => 2024], ['name' => '55e édition']);
        $edition2025 = Edition::updateOrCreate(['year' => 2025], ['name' => '56e édition']);
        $currentEdition = $edition2025;

        // 3. Dicastries (Départements)
        $dicastrySponso = Dicastry::create(['name' => 'Sponsoring', 'order_column' => 1]);
        $dicastryTech = Dicastry::create(['name' => 'Technique', 'order_column' => 2]);
        $dicastryAdmin = Dicastry::create(['name' => 'Administration', 'order_column' => 3]);

        // 4. Categories (Clients & Contacts)
        $catPrincipal = ClientCategory::create(['name' => 'Partenaires Principaux', 'color' => 'success']);
        $catPme = ClientCategory::create(['name' => 'PME Locales', 'color' => 'info']);
        $catAssoc = ClientCategory::create(['name' => 'Associations', 'color' => 'warning']);
        $catPrivate = ClientCategory::create(['name' => 'Privés', 'color' => 'gray']);

        $contactCatVIP = ContactCategory::create(['name' => 'VIP', 'color' => 'success']);
        $contactCatAmis = ContactCategory::create(['name' => 'Amis', 'color' => 'info']);
        $contactCatComite = ContactCategory::create(['name' => 'Comité', 'color' => 'danger']);
        $contactCatEntreprise = ContactCategory::create(['name' => 'Entreprises', 'color' => 'primary']);

        // 5. Products (Items billable)
        $prodPage1 = Product::create([
            'edition_id'  => $currentEdition->id,
            'name'        => 'Page Publicitaire 1/1',
            'cost'        => 1000.00,
            'tax_rate'    => 8.1,
            'include_vat' => false,
        ]);

        $prodPageHalf = Product::create([
            'edition_id'  => $currentEdition->id,
            'name'        => 'Page Publicitaire 1/2',
            'cost'        => 600.00,
            'tax_rate'    => 8.1,
            'include_vat' => false,
        ]);

        $prodBanner = Product::create([
            'edition_id'  => $currentEdition->id,
            'name'        => 'Pose Banderole',
            'cost'        => 500.00,
            'tax_rate'    => 8.1,
            'include_vat' => false,
        ]);

        // 6. Categories (Provisions/Prestations)
        $provCatJournal = ProvisionCategory::create(['name' => 'Journal de fête']);
        $provCatBanner = ProvisionCategory::create(['name' => 'Banderoles']);
        $provCatScreen = ProvisionCategory::create(['name' => 'Ecrans Géants']);
        $provCatPack = ProvisionCategory::create(['name' => 'Packs Sponsoring']);

        // 7. Provisions (The Catalog) linked to Products

        // Journal Provisions
        $provPage1 = Provision::create([
            'edition_id'            => $currentEdition->id,
            'category_id'           => $provCatJournal->id,
            'dicastry_id'           => $dicastrySponso->id,
            'product_id'            => $prodPage1->id, // Linked!
            'name'                  => 'Annonce 1/1 page',
            'code'                  => 'J-1-1',
            'has_product'           => true,
            'has_media'             => true, // Needs PDF upload
            'has_numeric_indicator' => false,
        ]);

        $provPageHalf = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => $provCatJournal->id,
            'dicastry_id' => $dicastrySponso->id,
            'product_id'  => $prodPageHalf->id, // Linked!
            'name'        => 'Annonce 1/2 page',
            'code'        => 'J-1-2',
            'has_product' => true,
            'has_media'   => true,
        ]);

        // Banner Provisions
        $provBannerFinish = Provision::create([
            'edition_id'                => $currentEdition->id,
            'category_id'               => $provCatBanner->id,
            'dicastry_id'               => $dicastryTech->id,
            'product_id'                => $prodBanner->id, // Linked!
            'name'                      => 'Banderole Arrivée',
            'code'                      => 'B-ARR',
            'has_product'               => true,
            'has_goods_to_be_delivered' => true, // Needs physical delivery
            'due_date_indicator'        => '20.11.2025', // Deadline text
        ]);

        // Special Provisions (Donation & VIP) - No Product link usually (variable price or free)
        $provDonation = Provision::create([
            'edition_id'            => $currentEdition->id,
            'category_id'           => null,
            'dicastry_id'           => $dicastryAdmin->id,
            'name'                  => 'Don de soutien',
            'code'                  => 'DON',
            'has_numeric_indicator' => true, // Amount entered manually
        ]);

        $provVip = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => null,
            'dicastry_id' => $dicastryAdmin->id,
            'name'        => 'Accès Espace VIP',
            'code'        => 'VIP',
            'has_vip'     => true, // Enables VIP guest list logic
        ]);

        // 8. POPULATE SETTINGS TABLE
        $this->updateSetting('edition_id', $currentEdition->id);
        $this->updateSetting('advertiser_form_client_category', $catPme->id);
        $this->updateSetting('advertiser_form_journal_category', $provCatJournal->id);
        $this->updateSetting('advertiser_form_banner_category', $provCatBanner->id);
        $this->updateSetting('advertiser_form_screen_category', $provCatScreen->id);
        $this->updateSetting('advertiser_form_pack_category', $provCatPack->id);
        $this->updateSetting('advertiser_form_donation_provision', $provDonation->id);
        $this->updateSetting('vip_provision', $provVip->id);
        $this->updateSetting('reports_advertisers_categories', [$catPrincipal->id, $catPme->id]);
        $this->updateSetting('reports_banners_provisions', [$provBannerFinish->id]);
        $this->updateSetting('reports_advertisers_journal_provisions', [$provPage1->id, $provPageHalf->id]);
        $this->updateSetting('reports_interclass_donor_provision', $provDonation->id);

        // ==========================================
        // SCENARIOS
        // ==========================================

        // Helper for Invoices
        $createPosition = fn ($name, $cost, $qty = 1) => [
            'name'        => $name,
            'cost'        => $cost,
            'quantity'    => $qty,
            'tax_rate'    => 8.1,
            'include_vat' => false,
        ];

        // --- SCENARIO 1: The Big Sponsor (Paid) ---
        // Client with Contacts (Director, Assistant)
        $clientBank = Client::factory()->create([
            'name'        => 'Banque Cantonale',
            'category_id' => $catPrincipal->id,
            'locality'    => 'Sion',
        ]);

        $contactDirector = Contact::factory()->create([
            'first_name'  => 'Pierre', 'last_name' => 'Dubois',
            'email'       => 'pierre.dubois@bcvs.ch',
            'role'        => 'Directeur',
            'category_id' => $contactCatVIP->id,
        ]);
        $contactAssistant = Contact::factory()->create([
            'first_name'  => 'Marie', 'last_name' => 'Claude',
            'email'       => 'marie.claude@bcvs.ch',
            'role'        => 'Assistante',
            'category_id' => $contactCatEntreprise->id,
        ]);

        $clientBank->contacts()->attach($contactDirector, ['type' => 'executive']); // Décideur
        $clientBank->contacts()->attach($contactAssistant, ['type' => 'administration']); // Admin

        // Provisions
        ProvisionElement::create([
            'edition_id'     => $currentEdition->id,
            'provision_id'   => $provPage1->id,
            'recipient_type' => Client::class,
            'recipient_id'   => $clientBank->id,
            'status'         => ProvisionElementStatusEnum::Confirmed,
            'quantity'       => 1,
            'cost'           => $prodPage1->cost, // Auto-filled from product ideally, but explicit here
            'tax_rate'       => $prodPage1->tax_rate,
            'media_status'   => 'received', // File uploaded
        ]);

        // VIP for the Director
        ProvisionElement::create([
            'edition_id'            => $currentEdition->id,
            'provision_id'          => $provVip->id,
            'recipient_type'        => Contact::class, // Attached to Contact!
            'recipient_id'          => $contactDirector->id,
            'status'                => ProvisionElementStatusEnum::Confirmed,
            'vip_invitation_number' => 2,
            'vip_response_status'   => 'accepted',
            'vip_guests'            => [['name' => 'Mme Dubois']], // +1 guest
            'cost'                  => 0,
        ]);

        // Invoice
        Invoice::create([
            'edition_id'   => $currentEdition->id,
            'client_id'    => $clientBank->id,
            'status'       => InvoiceStatusEnum::Paid,
            'title'        => 'Facture 2025001',
            'number'       => '2025001',
            'qr_reference' => QrPaymentReferenceGenerator::generate(null, '2025001'),
            'date'         => now()->subMonth(),
            'due_date'     => now()->subMonth()->addDays(30),
            'paid_on'      => now()->subDays(5),
            'positions'    => [
                $createPosition($prodPage1->name, $prodPage1->cost),
                $createPosition('Sponsoring Principal (Pack)', 4000),
            ],
        ]);

        $this->ensureEngagement($clientBank, $currentEdition, 'paid');

        // --- SCENARIO 2: The SME (Sent, Unpaid) ---
        // Client with Marketing Contact
        $clientGarage = Client::factory()->create([
            'name'        => 'Garage du Centre',
            'category_id' => $catPme->id,
            'locality'    => 'Martigny',
        ]);

        $contactMarketing = Contact::factory()->create([
            'first_name'  => 'Jean', 'last_name' => 'Michel',
            'email'       => 'marketing@garage-centre.ch',
            'category_id' => $contactCatEntreprise->id,
        ]);
        $clientGarage->contacts()->attach($contactMarketing, ['type' => 'commercial']);

        ProvisionElement::create([
            'edition_id'     => $currentEdition->id,
            'provision_id'   => $provPageHalf->id,
            'recipient_type' => Client::class,
            'recipient_id'   => $clientGarage->id,
            'status'         => ProvisionElementStatusEnum::Confirmed,
            'quantity'       => 1,
            'cost'           => $prodPageHalf->cost,
            'media_status'   => 'missing', // Alert!
        ]);

        ProvisionElement::create([
            'edition_id'            => $currentEdition->id,
            'provision_id'          => $provBannerFinish->id,
            'recipient_type'        => Client::class,
            'recipient_id'          => $clientGarage->id,
            'status'                => ProvisionElementStatusEnum::ToPrepare,
            'quantity'              => 1,
            'cost'                  => $prodBanner->cost,
            'goods_to_be_delivered' => 'received', // Banderole livrée au local
        ]);

        Invoice::create([
            'edition_id'   => $currentEdition->id,
            'client_id'    => $clientGarage->id,
            'status'       => InvoiceStatusEnum::Sent,
            'title'        => 'Facture 2025002',
            'number'       => '2025002',
            'qr_reference' => QrPaymentReferenceGenerator::generate(null, '2025002'),
            'date'         => now()->subDays(10),
            'due_date'     => now()->subDays(10)->addDays(30),
            'positions'    => [
                $createPosition($prodPageHalf->name, $prodPageHalf->cost),
                $createPosition($prodBanner->name, $prodBanner->cost),
            ],
        ]);

        $this->ensureEngagement($clientGarage, $currentEdition, 'billed');

        // --- SCENARIO 3: Individual Donor (No Product, Numeric Indicator) ---
        $clientPrivate = Client::factory()->create([
            'name'        => 'Jean Dupont',
            'category_id' => $catPrivate->id,
        ]);

        ProvisionElement::create([
            'edition_id'        => $currentEdition->id,
            'provision_id'      => $provDonation->id,
            'recipient_type'    => Client::class,
            'recipient_id'      => $clientPrivate->id,
            'status'            => ProvisionElementStatusEnum::Confirmed,
            'numeric_indicator' => 100, // Montant du don
            'textual_indicator' => 'Souhaite rester anonyme',
            'cost'              => 100,
            'tax_rate'          => null,
        ]);

        Invoice::create([
            'edition_id'   => $currentEdition->id,
            'client_id'    => $clientPrivate->id,
            'status'       => InvoiceStatusEnum::Paid,
            'title'        => 'Facture 2025004',
            'number'       => '2025004',
            'qr_reference' => QrPaymentReferenceGenerator::generate(null, '2025004'),
            'date'         => now()->subMonth(),
            'due_date'     => now()->subMonth()->addDays(30),
            'paid_on'      => now()->subDays(15),
            'positions'    => [
                $createPosition('Don de soutien', 100, 1),
            ],
        ]);

        $this->ensureEngagement($clientPrivate, $currentEdition, 'paid');

        // --- SCENARIO 4: VIP Guest Only (Committee Member) ---
        $contactComite = Contact::factory()->create([
            'first_name'  => 'Sarah', 'last_name' => 'Connor',
            'category_id' => $contactCatComite->id,
        ]);

        ProvisionElement::create([
            'edition_id'            => $currentEdition->id,
            'provision_id'          => $provVip->id,
            'recipient_type'        => Contact::class, // Direct to contact
            'recipient_id'          => $contactComite->id,
            'status'                => ProvisionElementStatusEnum::Confirmed,
            'vip_invitation_number' => 1,
            'vip_response_status'   => 'pending', // No response yet
            'due_date'              => now()->addDays(15), // Reply deadline
            'cost'                  => 0,
        ]);

        // --- SCENARIO 5: Pro Forma / Draft with Note ---
        $clientProspect = Client::factory()->create([
            'name'        => 'Start-up Tech',
            'category_id' => $catPme->id,
        ]);

        Invoice::create([
            'edition_id'   => $currentEdition->id,
            'client_id'    => $clientProspect->id,
            'status'       => InvoiceStatusEnum::Draft,
            'title'        => 'Facture Pro Forma',
            'number'       => '2025005',
            'qr_reference' => QrPaymentReferenceGenerator::generate(null, '2025005'),
            'date'         => now(),
            'due_date'     => now()->addDays(30),
            'is_pro_forma' => true,
            'positions'    => [
                $createPosition('Offre Sponsoring Global', 3500),
            ],
            'note' => 'En attente de validation du budget marketing.',
        ]);

        $this->ensureEngagement($clientProspect, $currentEdition, 'prospect');

        // Fillers
        Contact::factory(10)->create();
    }

    /**
     * Helper to update the settings table (json key-value store).
     */
    private function updateSetting(string $key, mixed $value): void
    {
        $table = config('settings.database_table_name', 'settings');

        DB::table($table)->updateOrInsert(
            ['key' => $key],
            [
                'value'      => json_encode($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Helper to create/update ClientEngagement
     */
    private function ensureEngagement($client, $edition, $stage, $status = null)
    {
        ClientEngagement::updateOrCreate(
            ['client_id' => $client->id, 'edition_id' => $edition->id],
            [
                'stage'  => $stage,
                'status' => $status,
            ]
        );
    }
}
