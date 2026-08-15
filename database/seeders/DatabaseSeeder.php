<?php

namespace Database\Seeders;

use App\Models\Run;
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

        $prodSchool = Product::create([
            'edition_id'  => $currentEdition->id,
            'name'        => 'Inscription interclasses',
            'cost'        => 20.00,
            'tax_rate'    => 0.0,
            'include_vat' => false,
        ]);

        $prodCompany = Product::create([
            'edition_id'  => $currentEdition->id,
            'name'        => 'Inscription Entreprise',
            'cost'        => 35.00,
            'tax_rate'    => 0.0,
            'include_vat' => false,
        ]);

        // 6. Categories (Provisions/Prestations)
        $provCatJournal = ProvisionCategory::create(['name' => 'Journal de fête']);
        $provCatBanner = ProvisionCategory::create(['name' => 'Banderoles']);
        $provCatScreen = ProvisionCategory::create(['name' => 'Ecrans Géants']);
        $provCatPack = ProvisionCategory::create(['name' => 'Packs Sponsoring']);

        // 7. Provisions (The Catalog) linked to Products
        $provPage1 = Provision::create([
            'edition_id'            => $currentEdition->id,
            'category_id'           => $provCatJournal->id,
            'dicastry_id'           => $dicastrySponso->id,
            'product_id'            => $prodPage1->id,
            'name'                  => 'Annonce 1/1 page',
            'code'                  => 'J-1-1',
            'has_product'           => true,
            'has_media'             => true,
            'has_numeric_indicator' => false,
        ]);

        $provPageHalf = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => $provCatJournal->id,
            'dicastry_id' => $dicastrySponso->id,
            'product_id'  => $prodPageHalf->id,
            'name'        => 'Annonce 1/2 page',
            'code'        => 'J-1-2',
            'has_product' => true,
            'has_media'   => true,
        ]);

        $provBannerFinish = Provision::create([
            'edition_id'                => $currentEdition->id,
            'category_id'               => $provCatBanner->id,
            'dicastry_id'               => $dicastryTech->id,
            'product_id'                => $prodBanner->id,
            'name'                      => 'Banderole Arrivée',
            'code'                      => 'B-ARR',
            'has_product'               => true,
            'has_goods_to_be_delivered' => true,
            'due_date_indicator'        => '20.11.2025',
        ]);

        $provSchool = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => null,
            'dicastry_id' => $dicastryAdmin->id,
            'product_id'  => $prodSchool->id,
            'name'        => 'Inscription interclasses',
            'code'        => 'INS-SCH',
            'has_product' => true,
        ]);

        $provCompany = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => null,
            'dicastry_id' => $dicastryAdmin->id,
            'product_id'  => $prodCompany->id,
            'name'        => 'Inscription Entreprise',
            'code'        => 'INS-ENT',
            'has_product' => true,
        ]);

        $provDonation = Provision::create([
            'edition_id'            => $currentEdition->id,
            'category_id'           => null,
            'dicastry_id'           => $dicastryAdmin->id,
            'name'                  => 'Don de soutien',
            'code'                  => 'DON',
            'has_numeric_indicator' => true,
        ]);

        $provVip = Provision::create([
            'edition_id'  => $currentEdition->id,
            'category_id' => null,
            'dicastry_id' => $dicastryAdmin->id,
            'name'        => 'Accès Espace VIP',
            'code'        => 'VIP',
            'has_vip'     => true,
        ]);

        // 8. Runs (Courses réelles selon horaire Course de Noël Sion / Datasport)
        $runTrailChateaux = Run::create([
            'name'                   => 'Trail des Châteaux',
            'distance'               => 20.00,
            'cost'                   => 50.00,
            'min_age'                => 18,
            'max_age'                => null,
            'available_for_types'    => ['group', 'company', 'elite'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-TRAIL-20K',
            'code'                   => 'RUN-TRAIL-20K',
            'accepts_voucher'        => true,
        ]);

        $runTrailChatelets = Run::create([
            'name'                   => 'Trail des Châtelets',
            'distance'               => 10.00,
            'cost'                   => 35.00,
            'min_age'                => 16,
            'max_age'                => null,
            'available_for_types'    => ['group', 'company'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-TRAIL-10K',
            'code'                   => 'RUN-TRAIL-10K',
            'accepts_voucher'        => true,
        ]);

        $runHommes = Run::create([
            'name'                   => 'Course Hommes',
            'distance'               => 7.30,
            'cost'                   => 30.00,
            'min_age'                => 18,
            'max_age'                => null,
            'available_for_types'    => ['group', 'elite'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-HOMMES',
            'code'                   => 'RUN-HOMMES',
            'accepts_voucher'        => true,
        ]);

        $runDames = Run::create([
            'name'                   => 'Course Dames',
            'distance'               => 5.20,
            'cost'                   => 30.00,
            'min_age'                => 18,
            'max_age'                => null,
            'available_for_types'    => ['group', 'elite'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-DAMES',
            'code'                   => 'RUN-DAMES',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Populaires et Médaille sportive sédunoise Walking + Nordic',
            'distance'               => 5.00,
            'cost'                   => 25.00,
            'min_age'                => 10,
            'max_age'                => null,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-WALK',
            'code'                   => 'RUN-WALK',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course adaptée (personnes en situation d\'handicap)',
            'distance'               => 2.00,
            'cost'                   => 0.00,
            'min_age'                => null,
            'max_age'                => null,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-ADAPTEE',
            'code'                   => 'RUN-ADAPTEE',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course des Pères/Mères Noël',
            'distance'               => 2.50,
            'cost'                   => 15.00,
            'min_age'                => null,
            'max_age'                => null,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-NOEL',
            'code'                   => 'RUN-NOEL',
            'accepts_voucher'        => true,
        ]);

        $runEntreprises = Run::create([
            'name'                => 'Challenge Entreprises',
            'distance'            => 5.00,
            'cost'                => 35.00,
            'min_age'             => 16,
            'max_age'             => null,
            'provision_id'        => $provCompany->id,
            'available_for_types' => ['company', 'group'],
            'start_blocs'         => [
                ['label' => 'Bloc 1 - 18h10', 'time' => '18:10'],
                ['label' => 'Bloc 2 - 18h25', 'time' => '18:25'],
                ['label' => 'Bloc 3 - 18h40', 'time' => '18:40'],
            ],
            'registrations_deadline' => now()->addDays(45),
            'registrations_limit'    => 500,
            'registrations_number'   => 45,
            'datasport_code'         => 'DS-ENTREPRISES',
            'code'                   => 'RUN-ENTREPRISES',
            'accepts_voucher'        => true,
        ]);

        $runInterclasses = Run::create([
            'name'                => 'Interclasses',
            'distance'            => 3.00,
            'cost'                => 20.00,
            'min_age'             => 6,
            'max_age'             => 16,
            'provision_id'        => $provSchool->id,
            'available_for_types' => ['school'],
            'start_blocs'         => [
                ['label' => 'Bloc Écoles 1', 'time' => '11:00'],
                ['label' => 'Bloc Écoles 2', 'time' => '11:20'],
            ],
            'registrations_deadline' => now()->addDays(45),
            'registrations_limit'    => 1000,
            'registrations_number'   => 150,
            'datasport_code'         => 'DS-INTERCLASSES',
            'code'                   => 'RUN-INTERCLASSES',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Famigros Run & Win',
            'distance'               => 1.00,
            'cost'                   => 0.00,
            'min_age'                => null,
            'max_age'                => null,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-FAMIGROS',
            'code'                   => 'RUN-FAMIGROS',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course des enfants - 1 Tour',
            'distance'               => 1.10,
            'cost'                   => 15.00,
            'min_age'                => 5,
            'max_age'                => 9,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-ENF-1T',
            'code'                   => 'RUN-ENF-1T',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course des enfants - 2 Tours',
            'distance'               => 2.20,
            'cost'                   => 15.00,
            'min_age'                => 10,
            'max_age'                => 13,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-ENF-2T',
            'code'                   => 'RUN-ENF-2T',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course des Cadets/Juniors - 3 Tours',
            'distance'               => 3.30,
            'cost'                   => 20.00,
            'min_age'                => 14,
            'max_age'                => 17,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-CAD-3T',
            'code'                   => 'RUN-CAD-3T',
            'accepts_voucher'        => true,
        ]);

        Run::create([
            'name'                   => 'Course des Cadets/Juniors - 4 Tours',
            'distance'               => 4.40,
            'cost'                   => 20.00,
            'min_age'                => 16,
            'max_age'                => 19,
            'available_for_types'    => ['group'],
            'registrations_deadline' => now()->addDays(45),
            'datasport_code'         => 'DS-CAD-4T',
            'code'                   => 'RUN-CAD-4T',
            'accepts_voucher'        => true,
        ]);

        // 9. POPULATE SETTINGS TABLE
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
        $this->updateSetting('registrations_deadline', now()->addDays(45)->format('Y-m-d H:i:s'));
        $this->updateSetting('default_run_school', $runInterclasses->id);
        $this->updateSetting('default_run_company', $runEntreprises->id);
        $this->updateSetting('default_run_elite', $runHommes->id);

        // ==========================================
        // SCENARIOS
        // ==========================================

        $createPosition = fn ($name, $cost, $qty = 1) => [
            'name'        => $name,
            'cost'        => $cost,
            'quantity'    => $qty,
            'tax_rate'    => 8.1,
            'include_vat' => false,
        ];

        // --- SCENARIO 1: The Big Sponsor (Paid) ---
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

        $clientBank->contacts()->attach($contactDirector, ['type' => 'executive']);
        $clientBank->contacts()->attach($contactAssistant, ['type' => 'administration']);

        ProvisionElement::create([
            'edition_id'     => $currentEdition->id,
            'provision_id'   => $provPage1->id,
            'recipient_type' => Client::class,
            'recipient_id'   => $clientBank->id,
            'status'         => ProvisionElementStatusEnum::Confirmed,
            'quantity'       => 1,
            'cost'           => $prodPage1->cost,
            'tax_rate'       => $prodPage1->tax_rate,
            'media_status'   => 'received',
        ]);

        ProvisionElement::create([
            'edition_id'            => $currentEdition->id,
            'provision_id'          => $provVip->id,
            'recipient_type'        => Contact::class,
            'recipient_id'          => $contactDirector->id,
            'status'                => ProvisionElementStatusEnum::Confirmed,
            'vip_invitation_number' => 2,
            'vip_response_status'   => 'accepted',
            'vip_guests'            => [['name' => 'Mme Dubois']],
            'cost'                  => 0,
        ]);

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
            'media_status'   => 'missing',
        ]);

        ProvisionElement::create([
            'edition_id'            => $currentEdition->id,
            'provision_id'          => $provBannerFinish->id,
            'recipient_type'        => Client::class,
            'recipient_id'          => $clientGarage->id,
            'status'                => ProvisionElementStatusEnum::ToPrepare,
            'quantity'              => 1,
            'cost'                  => $prodBanner->cost,
            'goods_to_be_delivered' => 'received',
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

        // RunRegistrations & Vouchers Seeder
        $this->call(RunRegistrationSeeder::class);

        // Fillers
        Contact::factory(10)->create();
    }

    private function updateSetting(string $key, mixed $value): void
    {
        $table = config('settings.database_table_name', 'settings');
        DB::table($table)->where('key', $key)->orWhere('key', 'like', $key.'.%')->delete();
        setting([$key => $value]);
    }

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
