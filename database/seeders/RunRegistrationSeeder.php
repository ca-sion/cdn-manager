<?php

namespace Database\Seeders;

use App\Models\Run;
use App\Enums\Gender;
use App\Models\Client;
use App\Models\Voucher;
use App\Helpers\AppHelper;
use App\Models\RunRegistration;
use Illuminate\Database\Seeder;
use App\Enums\RunRegistrationType;
use App\Models\RunRegistrationElement;
use App\Services\RunRegistrationService;

class RunRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

        // 1. Récupérer ou créer les courses de référence
        $runSchool = Run::where('name', 'like', '%Interclasses%')->first() ?? Run::firstOrCreate(
            ['name' => 'Interclasses'],
            ['cost' => 20.00, 'available_for_types' => ['school'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runElite = Run::where('name', 'like', '%Course Hommes%')->orWhere('name', 'like', '%Trail des Châteaux%')->first() ?? Run::firstOrCreate(
            ['name' => 'Course Hommes'],
            ['cost' => 30.00, 'available_for_types' => ['group', 'elite'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runCompany = Run::where('name', 'like', '%Challenge Entreprises%')->orWhere('name', 'like', '%Entreprises%')->first() ?? Run::firstOrCreate(
            ['name' => 'Challenge Entreprises'],
            ['cost' => 35.00, 'available_for_types' => ['company', 'group'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runGroup = Run::where('name', 'like', '%Trail des Châteaux%')->first() ?? Run::firstOrCreate(
            ['name' => 'Trail des Châteaux'],
            ['cost' => 50.00, 'available_for_types' => ['group', 'company'], 'registrations_deadline' => now()->addDays(30)]
        );

        // 2. CAS 1 : Entreprise liée à un Client ("UBS SA") avec Facture Consolidée
        $clientUbs = Client::firstOrCreate(
            ['name' => 'UBS SA Sion'],
            [
                'address'               => 'Place du Midi 12',
                'postal_code'           => '1950',
                'locality'              => 'Sion',
                'email'                 => 'sponsoring@ubs-valais.ch',
                'phone'                 => '+41 27 324 11 11',
                'invoicing_email'       => 'comptabilite@ubs-valais.ch',
                'invoicing_address'     => 'Place du Midi 12',
                'invoicing_postal_code' => '1950',
                'invoicing_locality'    => 'Sion',
            ]
        );

        $regCompany = RunRegistration::create([
            'client_id'              => $clientUbs->id,
            'run_registration_type'  => RunRegistrationType::Company,
            'company_name'           => 'UBS SA - Equipe Valais',
            'company_bloc'           => 'Bloc 1 - 18h10',
            'contact_first_name'     => 'Marc',
            'contact_last_name'      => 'Dubuis',
            'contact_email'          => 'marc.dubuis@ubs.com',
            'contact_phone'          => '+41 79 123 45 67',
            'invoicing_company_name' => 'UBS SA',
            'invoicing_address'      => 'Place du Midi 12',
            'invoicing_postal_code'  => '1950',
            'invoicing_locality'     => 'Sion',
            'invoicing_email'        => 'comptabilite@ubs-valais.ch',
        ]);

        $companyRunners = [
            ['first_name' => 'Jean', 'last_name' => 'Favre', 'birthdate' => '1988-05-14', 'gender' => Gender::Male, 'email' => 'jean.favre@ubs.com'],
            ['first_name' => 'Sophie', 'last_name' => 'Martin', 'birthdate' => '1992-11-20', 'gender' => Gender::Female, 'email' => 'sophie.martin@ubs.com'],
            ['first_name' => 'Luc', 'last_name' => 'Rey', 'birthdate' => '1985-03-02', 'gender' => Gender::Male, 'email' => 'luc.rey@ubs.com'],
            ['first_name' => 'Carole', 'last_name' => 'Bonvin', 'birthdate' => '1990-08-15', 'gender' => Gender::Female, 'email' => 'carole.bonvin@ubs.com'],
            ['first_name' => 'Alexandre', 'last_name' => 'Fournier', 'birthdate' => '1995-01-30', 'gender' => Gender::Male, 'email' => 'alexandre.fournier@ubs.com'],
        ];

        foreach ($companyRunners as $runner) {
            RunRegistrationElement::create(array_merge($runner, [
                'run_registration_id'       => $regCompany->id,
                'run_id'                    => $runCompany->id,
                'run_name'                  => $runCompany->name,
                'team'                      => 'UBS SA - Equipe Valais',
                'bloc'                      => 'Bloc 1 - 18h10',
                'nationality'               => 'SUI',
                'has_free_registration_fee' => false,
            ]));
        }

        // Génération de la facture consolidée pour UBS SA
        try {
            app(RunRegistrationService::class)->createInvoiceForClient($clientUbs->id);
        } catch (\Throwable $e) {
            // Ignore if error during seed
        }

        // 3. CAS 2 : École ("École du Sacré-Cœur")
        $clientSchool = Client::firstOrCreate(
            ['name' => 'École du Sacré-Cœur Sion'],
            [
                'address'     => 'Rue du Collège 8',
                'postal_code' => '1950',
                'locality'    => 'Sion',
                'email'       => 'direction@sacrecoeur-sion.ch',
            ]
        );

        $regSchool = RunRegistration::create([
            'client_id'                      => $clientSchool->id,
            'run_registration_type'          => RunRegistrationType::School,
            'school_name'                    => 'École du Sacré-Cœur',
            'school_postal_code'             => '1950',
            'school_locality'                => 'Sion',
            'school_country'                 => 'SUI',
            'school_class_level'             => '8H',
            'school_class_holder_first_name' => 'Isabelle',
            'school_class_holder_last_name'  => 'Emery',
            'school_class_holder_email'      => 'isabelle.emery@sacrecoeur-sion.ch',
            'contact_first_name'             => 'Isabelle',
            'contact_last_name'              => 'Emery',
            'contact_email'                  => 'isabelle.emery@sacrecoeur-sion.ch',
            'contact_phone'                  => '+41 27 322 00 11',
        ]);

        $students = [
            ['first_name' => 'Lucas', 'last_name' => 'Bessel', 'birthdate' => '2014-04-12', 'gender' => Gender::Male],
            ['first_name' => 'Emma', 'last_name' => 'Caloz', 'birthdate' => '2014-09-25', 'gender' => Gender::Female],
            ['first_name' => 'Mathieu', 'last_name' => 'Dayer', 'birthdate' => '2014-01-08', 'gender' => Gender::Male],
            ['first_name' => 'Chloe', 'last_name' => 'Zufferey', 'birthdate' => '2014-06-18', 'gender' => Gender::Female],
        ];

        foreach ($students as $student) {
            RunRegistrationElement::create(array_merge($student, [
                'run_registration_id'       => $regSchool->id,
                'run_id'                    => $runSchool->id,
                'run_name'                  => $runSchool->name,
                'nationality'               => 'SUI',
                'has_free_registration_fee' => false,
            ]));
        }

        // 4. CAS 3 : Groupe / Inscription non liée à un client
        $regGroup = RunRegistration::create([
            'client_id'             => null,
            'run_registration_type' => RunRegistrationType::Group,
            'contact_first_name'    => 'Antoine',
            'contact_last_name'     => 'Clivaz',
            'contact_email'         => 'antoine.clivaz@gmail.com',
            'contact_phone'         => '+41 78 987 65 43',
        ]);

        RunRegistrationElement::create([
            'run_registration_id'       => $regGroup->id,
            'run_id'                    => $runGroup->id,
            'run_name'                  => $runGroup->name,
            'first_name'                => 'Antoine',
            'last_name'                 => 'Clivaz',
            'birthdate'                 => '1991-07-04',
            'gender'                    => Gender::Male,
            'nationality'               => 'SUI',
            'email'                     => 'antoine.clivaz@gmail.com',
            'team'                      => 'Les Courreurs du Samedi',
            'has_free_registration_fee' => false,
        ]);

        // 5. CAS 4 : Coureurs Élite (Tadesse Abraham, Julien Wanders)
        $elites = [
            [
                'first_name'                      => 'Tadesse',
                'last_name'                       => 'Abraham',
                'birthdate'                       => '1982-08-12',
                'gender'                          => Gender::Male,
                'nationality'                     => 'SUI',
                'email'                           => 'tadesse.abraham@running.ch',
                'address'                         => 'Avenue de la Gare 15',
                'postal_code'                     => '1201',
                'locality'                        => 'Genève',
                'country'                         => 'SUI',
                'iban'                            => 'CH93 0000 0000 0000 1234 A',
                'has_bonus_start'                 => true,
                'bonus_start_amount'              => 1500.00,
                'bonus_ranking_amount'            => 800.00,
                'bonus_arrival_amount'            => 500.00,
                'has_accommodation'               => true,
                'accommodation_friday'            => true,
                'accommodation_saturday'          => true,
                'accommodation_precision'         => 'Hôtel Elite Sion - Chambre Simple avec petit déjeuner',
                'has_expense_reimbursement'       => true,
                'expense_reimbursement_precision' => 'Billet de train 1ère classe A/R',
            ],
            [
                'first_name'                => 'Helen',
                'last_name'                 => 'Bekele',
                'birthdate'                 => '1994-11-21',
                'gender'                    => Gender::Female,
                'nationality'               => 'ETH',
                'email'                     => 'helen.bekele@athletics.org',
                'address'                   => 'Rue de Lausanne 40',
                'postal_code'               => '1000',
                'locality'                  => 'Lausanne',
                'country'                   => 'SUI',
                'iban'                      => 'CH44 0070 0000 9876 5432 B',
                'has_bonus_start'           => true,
                'bonus_start_amount'        => 1200.00,
                'bonus_ranking_amount'      => 600.00,
                'has_accommodation'         => true,
                'accommodation_saturday'    => true,
                'accommodation_precision'   => 'Hôtel Ibis Sion - Chambre double',
                'has_expense_reimbursement' => false,
            ],
        ];

        foreach ($elites as $eliteData) {
            $regElite = RunRegistration::create([
                'client_id'             => null,
                'run_registration_type' => RunRegistrationType::Elite,
                'contact_first_name'    => $eliteData['first_name'],
                'contact_last_name'     => $eliteData['last_name'],
                'contact_email'         => $eliteData['email'],
                'invoicing_email'       => $eliteData['email'],
                'payment_iban'          => $eliteData['iban'],
            ]);

            RunRegistrationElement::create(array_merge($eliteData, [
                'run_registration_id'       => $regElite->id,
                'run_id'                    => $runElite->id,
                'run_name'                  => $runElite->name,
                'bloc'                      => 'Bloc Élite - 17h30',
                'has_free_registration_fee' => true,
            ]));
        }

        // 6. CAS 5 : Vouchers / Dossards offerts
        $vouchers = [
            ['code' => 'DS-2026-UBS1', 'client_id' => $clientUbs->id, 'is_used' => true, 'used_at' => now()->subDays(2)],
            ['code' => 'DS-2026-UBS2', 'client_id' => $clientUbs->id, 'is_used' => false],
            ['code' => 'DS-2026-SC01', 'client_id' => $clientSchool->id, 'is_used' => false],
            ['code' => 'DS-2026-FREE', 'client_id' => null, 'is_used' => false],
            ['code' => 'DS-2026-VIP1', 'client_id' => null, 'is_used' => false],
        ];

        foreach ($vouchers as $v) {
            Voucher::firstOrCreate(
                ['code' => $v['code']],
                [
                    'edition_id' => $editionId,
                    'client_id'  => $v['client_id'],
                    'is_used'    => $v['is_used'],
                    'used_at'    => $v['used_at'] ?? null,
                ]
            );
        }
    }
}
