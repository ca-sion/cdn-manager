<?php

namespace Database\Seeders;

use App\Models\Run;
use App\Enums\Gender;
use App\Models\Client;
use App\Models\School;
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

        // 1. Récupérer ou créer les courses de référence avec genre et restriction d'âge
        $runSchool = Run::where('name', 'like', '%Interclasses%')->first() ?? Run::firstOrCreate(
            ['name' => 'Interclasses'],
            ['cost' => 20.00, 'min_age' => 6, 'max_age' => 16, 'available_for_types' => ['school'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runHommes = Run::where('name', 'like', '%Course Hommes%')->first() ?? Run::firstOrCreate(
            ['name' => 'Course Hommes'],
            ['gender' => 'M', 'cost' => 30.00, 'min_age' => 18, 'available_for_types' => ['group', 'elite'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runDames = Run::where('name', 'like', '%Course Dames%')->first() ?? Run::firstOrCreate(
            ['name' => 'Course Dames'],
            ['gender' => 'F', 'cost' => 30.00, 'min_age' => 18, 'available_for_types' => ['group', 'elite'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runCadettes3T = Run::where('name', 'like', '%Cadettes%')->first() ?? Run::firstOrCreate(
            ['name' => 'Course des Cadettes - 3 Tours'],
            ['gender' => 'F', 'cost' => 20.00, 'min_age' => 14, 'max_age' => 17, 'available_for_types' => ['group'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runCadets4T = Run::where('name', 'like', '%Cadets%')->first() ?? Run::firstOrCreate(
            ['name' => 'Course des Cadets - 4 Tours'],
            ['gender' => 'M', 'cost' => 20.00, 'min_age' => 14, 'max_age' => 17, 'available_for_types' => ['group'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runCompany = Run::where('name', 'like', '%Challenge Entreprises%')->orWhere('name', 'like', '%Entreprises%')->first() ?? Run::firstOrCreate(
            ['name' => 'Challenge Entreprises'],
            ['cost' => 35.00, 'min_age' => 16, 'available_for_types' => ['company', 'group'], 'registrations_deadline' => now()->addDays(30)]
        );

        $runGroup = Run::where('name', 'like', '%Trail des Châteaux%')->first() ?? Run::firstOrCreate(
            ['name' => 'Trail des Châteaux'],
            ['cost' => 50.00, 'min_age' => 18, 'available_for_types' => ['group', 'company'], 'registrations_deadline' => now()->addDays(30)]
        );

        // 2. Création des Clients & Écoles officielles
        $clientVilleSion = Client::firstOrCreate(
            ['name' => 'Ville de Sion - Administration des Écoles'],
            [
                'address'               => 'Rue de Lausanne 23',
                'postal_code'           => '1950',
                'locality'              => 'Sion',
                'email'                 => 'ecoles@sion.ch',
                'phone'                 => '+41 27 324 11 11',
                'invoicing_email'       => 'finances@sion.ch',
                'invoicing_address'     => 'Rue de Lausanne 23',
                'invoicing_postal_code' => '1950',
                'invoicing_locality'    => 'Sion',
            ]
        );

        $clientSacreCoeur = Client::firstOrCreate(
            ['name' => 'École du Sacré-Cœur Sion'],
            [
                'address'     => 'Rue du Collège 8',
                'postal_code' => '1950',
                'locality'    => 'Sion',
                'email'       => 'direction@sacrecoeur-sion.ch',
            ]
        );

        $schoolStGuerin = School::firstOrCreate(
            ['name' => 'Centre scolaire de St-Guérin'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientVilleSion->id, 'is_active' => true]
        );

        $schoolChampsec = School::firstOrCreate(
            ['name' => 'Centre scolaire de Champsec'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientVilleSion->id, 'is_active' => true]
        );

        $schoolGravelone = School::firstOrCreate(
            ['name' => 'Centre scolaire de Gravelone'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientVilleSion->id, 'is_active' => true]
        );

        $schoolChateauneuf = School::firstOrCreate(
            ['name' => 'Centre scolaire de Châteauneuf'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientVilleSion->id, 'is_active' => true]
        );

        $schoolPlatta = School::firstOrCreate(
            ['name' => 'Centre scolaire de Platta'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientVilleSion->id, 'is_active' => true]
        );

        $schoolSacreCoeur = School::firstOrCreate(
            ['name' => 'École du Sacré-Cœur'],
            ['postal_code' => '1950', 'locality' => 'Sion', 'country' => 'SUI', 'client_id' => $clientSacreCoeur->id, 'is_active' => true]
        );

        $schoolSaviese = School::firstOrCreate(
            ['name' => 'Centre scolaire de Savièse - Moréchon'],
            ['postal_code' => '1965', 'locality' => 'Savièse', 'country' => 'SUI', 'client_id' => null, 'is_active' => true]
        );

        $schoolConthey = School::firstOrCreate(
            ['name' => 'Centre scolaire de Conthey'],
            ['postal_code' => '1964', 'locality' => 'Conthey', 'country' => 'SUI', 'client_id' => null, 'is_active' => true]
        );

        // 3. SEEDING ÉCOLES / INTERCLASSES (Plusieurs degrés: 3H, 4H, 5H, 6H, 8H)
        $schoolClasses = [
            // 1. St-Guérin 3H : CONFORME (10 élèves, 5 filles)
            [
                'school'     => $schoolStGuerin,
                'level'      => '3H',
                'teacher_fn' => 'Céline',
                'teacher_ln' => 'Bonvin',
                'teacher_em' => 'celine.bonvin@ecoles-sion.ch',
                'phone'      => '+41 79 111 22 33',
                'students'   => [
                    ['first_name' => 'Léo', 'last_name' => 'Favre', 'birthdate' => '2018-03-12', 'gender' => Gender::Male],
                    ['first_name' => 'Emma', 'last_name' => 'Caloz', 'birthdate' => '2018-07-25', 'gender' => Gender::Female],
                    ['first_name' => 'Noah', 'last_name' => 'Fournier', 'birthdate' => '2018-01-14', 'gender' => Gender::Male],
                    ['first_name' => 'Mia', 'last_name' => 'Rey', 'birthdate' => '2018-09-30', 'gender' => Gender::Female],
                    ['first_name' => 'Arthur', 'last_name' => 'Dubuis', 'birthdate' => '2018-11-05', 'gender' => Gender::Male],
                    ['first_name' => 'Chloé', 'last_name' => 'Bagnoud', 'birthdate' => '2018-05-18', 'gender' => Gender::Female],
                    ['first_name' => 'Robin', 'last_name' => 'Darioly', 'birthdate' => '2018-08-20', 'gender' => Gender::Male],
                    ['first_name' => 'Juliette', 'last_name' => 'Mayor', 'birthdate' => '2018-02-14', 'gender' => Gender::Female],
                    ['first_name' => 'Maxime', 'last_name' => 'Moix', 'birthdate' => '2018-10-09', 'gender' => Gender::Male],
                    ['first_name' => 'Elena', 'last_name' => 'Glassey', 'birthdate' => '2018-12-03', 'gender' => Gender::Female],
                ],
            ],
            // 2. Sacré-Cœur 8H : CONFORME (9 élèves, 4 filles)
            [
                'school'     => $schoolSacreCoeur,
                'level'      => '8H',
                'teacher_fn' => 'Isabelle',
                'teacher_ln' => 'Emery',
                'teacher_em' => 'isabelle.emery@sacrecoeur-sion.ch',
                'phone'      => '+41 27 322 00 11',
                'students'   => [
                    ['first_name' => 'Lucas', 'last_name' => 'Bessel', 'birthdate' => '2013-04-12', 'gender' => Gender::Male],
                    ['first_name' => 'Emma', 'last_name' => 'Caloz', 'birthdate' => '2013-09-25', 'gender' => Gender::Female],
                    ['first_name' => 'Mathieu', 'last_name' => 'Dayer', 'birthdate' => '2013-01-08', 'gender' => Gender::Male],
                    ['first_name' => 'Chloé', 'last_name' => 'Zufferey', 'birthdate' => '2013-06-18', 'gender' => Gender::Female],
                    ['first_name' => 'Samuel', 'last_name' => 'Anthamatten', 'birthdate' => '2013-03-22', 'gender' => Gender::Male],
                    ['first_name' => 'Laura', 'last_name' => 'Balet', 'birthdate' => '2013-11-09', 'gender' => Gender::Female],
                    ['first_name' => 'Bastien', 'last_name' => 'Pralong', 'birthdate' => '2013-05-14', 'gender' => Gender::Male],
                    ['first_name' => 'Zoé', 'last_name' => 'Clivaz', 'birthdate' => '2013-07-30', 'gender' => Gender::Female],
                    ['first_name' => 'David', 'last_name' => 'Kuonen', 'birthdate' => '2013-10-02', 'gender' => Gender::Male],
                ],
            ],
            // 3. Savièse 6H : CONFORME (8 élèves, 4 filles)
            [
                'school'     => $schoolSaviese,
                'level'      => '6H',
                'teacher_fn' => 'Alexandre',
                'teacher_ln' => 'Dubuis',
                'teacher_em' => 'alexandre.dubuis@ecoles-saviese.ch',
                'phone'      => '+41 79 555 66 77',
                'students'   => [
                    ['first_name' => 'Alexis', 'last_name' => 'Dubuis', 'birthdate' => '2015-02-11', 'gender' => Gender::Male],
                    ['first_name' => 'Manon', 'last_name' => 'Debons', 'birthdate' => '2015-06-19', 'gender' => Gender::Female],
                    ['first_name' => 'Romain', 'last_name' => 'Varone', 'birthdate' => '2015-04-03', 'gender' => Gender::Male],
                    ['first_name' => 'Léa', 'last_name' => 'Héritier', 'birthdate' => '2015-09-27', 'gender' => Gender::Female],
                    ['first_name' => 'Simon', 'last_name' => 'Roten', 'birthdate' => '2015-01-15', 'gender' => Gender::Male],
                    ['first_name' => 'Camille', 'last_name' => 'Reynard', 'birthdate' => '2015-11-08', 'gender' => Gender::Female],
                    ['first_name' => 'Thomas', 'last_name' => 'Lathion', 'birthdate' => '2015-08-12', 'gender' => Gender::Male],
                    ['first_name' => 'Sarah', 'last_name' => 'Pitteloud', 'birthdate' => '2015-05-23', 'gender' => Gender::Female],
                ],
            ],
            // 4. Champsec 3H : INCOMPLET (< 8 élèves, 4 élèves, 2 filles)
            [
                'school'     => $schoolChampsec,
                'level'      => '3H',
                'teacher_fn' => 'Julien',
                'teacher_ln' => 'Roux',
                'teacher_em' => 'julien.roux@ecoles-sion.ch',
                'phone'      => '+41 79 222 33 44',
                'students'   => [
                    ['first_name' => 'Gabriel', 'last_name' => 'Martin', 'birthdate' => '2018-04-02', 'gender' => Gender::Male],
                    ['first_name' => 'Alice', 'last_name' => 'Zufferey', 'birthdate' => '2018-06-19', 'gender' => Gender::Female],
                    ['first_name' => 'Lucas', 'last_name' => 'Clivaz', 'birthdate' => '2018-02-28', 'gender' => Gender::Male],
                    ['first_name' => 'Léa', 'last_name' => 'Mottet', 'birthdate' => '2018-10-15', 'gender' => Gender::Female],
                ],
            ],
            // 5. Gravelone 4H : INCOMPLET (< 3 filles : 8 élèves mais seulement 2 filles)
            [
                'school'     => $schoolGravelone,
                'level'      => '4H',
                'teacher_fn' => 'Patricia',
                'teacher_ln' => 'Sierro',
                'teacher_em' => 'patricia.sierro@ecoles-sion.ch',
                'phone'      => '+41 79 333 44 55',
                'students'   => [
                    ['first_name' => 'Maxime', 'last_name' => 'Pannatier', 'birthdate' => '2017-02-10', 'gender' => Gender::Male],
                    ['first_name' => 'Thomas', 'last_name' => 'Morisod', 'birthdate' => '2017-05-22', 'gender' => Gender::Male],
                    ['first_name' => 'Théo', 'last_name' => 'Germanier', 'birthdate' => '2017-07-11', 'gender' => Gender::Male],
                    ['first_name' => 'Jules', 'last_name' => 'Favre', 'birthdate' => '2017-03-18', 'gender' => Gender::Male],
                    ['first_name' => 'Adrien', 'last_name' => 'Roux', 'birthdate' => '2017-10-05', 'gender' => Gender::Male],
                    ['first_name' => 'Nolan', 'last_name' => 'Emery', 'birthdate' => '2017-11-21', 'gender' => Gender::Male],
                    ['first_name' => 'Zoé', 'last_name' => 'Constantin', 'birthdate' => '2017-08-14', 'gender' => Gender::Female],
                    ['first_name' => 'Eva', 'last_name' => 'Bessel', 'birthdate' => '2017-12-01', 'gender' => Gender::Female],
                ],
            ],
            // 6. Châteauneuf 5H : INCOMPLET (< 8 élèves : 6 élèves, 3 filles)
            [
                'school'     => $schoolChateauneuf,
                'level'      => '5H',
                'teacher_fn' => 'François',
                'teacher_ln' => 'Emery',
                'teacher_em' => 'francois.emery@ecoles-sion.ch',
                'phone'      => '+41 79 444 55 66',
                'students'   => [
                    ['first_name' => 'Nathan', 'last_name' => 'Besse', 'birthdate' => '2016-03-15', 'gender' => Gender::Male],
                    ['first_name' => 'Sarah', 'last_name' => 'Gillioz', 'birthdate' => '2016-07-20', 'gender' => Gender::Female],
                    ['first_name' => 'Louis', 'last_name' => 'Masserey', 'birthdate' => '2016-11-04', 'gender' => Gender::Male],
                    ['first_name' => 'Clara', 'last_name' => 'Germanier', 'birthdate' => '2016-01-29', 'gender' => Gender::Female],
                    ['first_name' => 'Jules', 'last_name' => 'Moix', 'birthdate' => '2016-08-11', 'gender' => Gender::Male],
                    ['first_name' => 'Lucie', 'last_name' => 'Bonvin', 'birthdate' => '2016-04-18', 'gender' => Gender::Female],
                ],
            ],
        ];

        foreach ($schoolClasses as $classData) {
            $regSchool = RunRegistration::create([
                'client_id'                      => $classData['school']->client_id,
                'school_id'                      => $classData['school']->id,
                'run_registration_type'          => RunRegistrationType::School,
                'school_name'                    => $classData['school']->name,
                'school_postal_code'             => $classData['school']->postal_code,
                'school_locality'                => $classData['school']->locality,
                'school_country'                 => $classData['school']->country ?: 'SUI',
                'school_class_level'             => $classData['level'],
                'school_class_holder_first_name' => $classData['teacher_fn'],
                'school_class_holder_last_name'  => $classData['teacher_ln'],
                'school_class_holder_email'      => $classData['teacher_em'],
                'school_class_holder_phone'      => $classData['phone'],
                'contact_first_name'             => $classData['teacher_fn'],
                'contact_last_name'              => $classData['teacher_ln'],
                'contact_email'                  => $classData['teacher_em'],
                'contact_phone'                  => $classData['phone'],
            ]);

            foreach ($classData['students'] as $student) {
                RunRegistrationElement::create(array_merge($student, [
                    'run_registration_id'       => $regSchool->id,
                    'run_id'                    => $runSchool->id,
                    'run_name'                  => $runSchool->name,
                    'nationality'               => 'SUI',
                    'has_free_registration_fee' => false,
                ]));
            }
        }

        // 4. SEEDING ENTREPRISE (UBS SA)
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

        try {
            app(RunRegistrationService::class)->createInvoiceForClient($clientUbs->id);
        } catch (\Throwable $e) {
            // Ignore if error
        }

        // 5. SEEDING GROUPES / CLUBS ATHLÉTIQUES (avec discrimination H/F sur Cadets et Adultes)
        $regGroupCA = RunRegistration::create([
            'client_id'             => null,
            'run_registration_type' => RunRegistrationType::Group,
            'company_name'          => 'CA Sion Jeunesse',
            'contact_first_name'    => 'Laurent',
            'contact_last_name'     => 'Meystre',
            'contact_email'         => 'laurent.meystre@casion.ch',
            'contact_phone'         => '+41 78 555 66 77',
        ]);

        $groupAthletes = [
            // Filles 2009 -> Cadettes 3 tours (F)
            ['first_name' => 'Alix', 'last_name' => 'Vogel', 'birthdate' => '2009-04-18', 'gender' => Gender::Female, 'email' => 'alix.vogel@casion.ch', 'run_id' => $runCadettes3T->id, 'run_name' => $runCadettes3T->name],
            ['first_name' => 'Noémie', 'last_name' => 'Délèze', 'birthdate' => '2009-09-02', 'gender' => Gender::Female, 'email' => 'noemie.deleze@casion.ch', 'run_id' => $runCadettes3T->id, 'run_name' => $runCadettes3T->name],
            // Garçons 2009 -> Cadets 4 tours (M)
            ['first_name' => 'Robin', 'last_name' => 'Bumann', 'birthdate' => '2009-02-11', 'gender' => Gender::Male, 'email' => 'robin.bumann@casion.ch', 'run_id' => $runCadets4T->id, 'run_name' => $runCadets4T->name],
            ['first_name' => 'Valentin', 'last_name' => 'Lattion', 'birthdate' => '2009-06-27', 'gender' => Gender::Male, 'email' => 'valentin.lattion@casion.ch', 'run_id' => $runCadets4T->id, 'run_name' => $runCadets4T->name],
            // Adultes
            ['first_name' => 'Stéphane', 'last_name' => 'Heiniger', 'birthdate' => '1989-10-05', 'gender' => Gender::Male, 'email' => 'stephane.heiniger@casion.ch', 'run_id' => $runHommes->id, 'run_name' => $runHommes->name],
            ['first_name' => 'Mélanie', 'last_name' => 'Praz', 'birthdate' => '1993-12-14', 'gender' => Gender::Female, 'email' => 'melanie.praz@casion.ch', 'run_id' => $runDames->id, 'run_name' => $runDames->name],
        ];

        foreach ($groupAthletes as $athlete) {
            RunRegistrationElement::create(array_merge($athlete, [
                'run_registration_id'       => $regGroupCA->id,
                'team'                      => 'CA Sion Jeunesse',
                'nationality'               => 'SUI',
                'has_free_registration_fee' => false,
            ]));
        }

        // 6. COUREURS ÉLITE
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
                'run_id'                    => ($eliteData['gender'] === Gender::Female ? $runDames->id : $runHommes->id),
                'run_name'                  => ($eliteData['gender'] === Gender::Female ? $runDames->name : $runHommes->name),
                'bloc'                      => 'Bloc Élite - 17h30',
                'has_free_registration_fee' => true,
            ]));
        }

        // 7. VOUCHERS
        $vouchers = [
            ['code' => 'DS-2026-UBS1', 'client_id' => $clientUbs->id, 'is_used' => true, 'used_at' => now()->subDays(2)],
            ['code' => 'DS-2026-UBS2', 'client_id' => $clientUbs->id, 'is_used' => false],
            ['code' => 'DS-2026-SC01', 'client_id' => $clientSacreCoeur->id, 'is_used' => false],
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
