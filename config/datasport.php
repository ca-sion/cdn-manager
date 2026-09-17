<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Datasport Startlist & Registration URLs
    |--------------------------------------------------------------------------
    | URL publique Datasport où scraper les listes de départ et le JSON __NEXT_DATA__.
    */
    'startlist_url' => env(
        'DATASPORT_STARTLIST_URL',
        'https://results.datasport.com/fr/course/course-de-noel-et-trail-des-chateaux-2026-sion/startlist'
    ),

    'registration_url' => env(
        'DATASPORT_REGISTRATION_URL',
        'https://onreg.datasport.com/course-de-noel-et-trail-des-chateaux-2026-sion'
    ),

    /*
    |--------------------------------------------------------------------------
    | Durée du Cache (en minutes)
    |--------------------------------------------------------------------------
    | Durée pendant laquelle les données scrapées sont conservées en cache
    | avant d'être rafraîchies à la prochaine visite (défaut : 15 minutes).
    */
    'cache_ttl_minutes' => (int) env('DATASPORT_CACHE_TTL_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Limite par niveau scolaire pour les Interclasses (3H à 8H)
    |--------------------------------------------------------------------------
    | Quota d'élèves par niveau scolaire (défaut : 200).
    */
    'interclasses_limit_per_level' => (int) env('DATASPORT_INTERCLASSES_LIMIT_PER_LEVEL', 200),

    /*
    |--------------------------------------------------------------------------
    | Limites et configurations par défaut des courses
    |--------------------------------------------------------------------------
    | Si une course n'a pas de 'registrations_limit' défini dans la base de données
    | (table 'runs'), cette configuration sert de fallback.
    | La clé peut être le slug Datasport ou le nom normalisé.
    */
    'default_limits' => [
        'trail-des-chateaux'                                               => 500,
        'trail-des-chatelets'                                              => 300,
        'challenge-entreprises'                                            => 2500,
        'interclasses'                                                     => 1200,
        'course-hommes'                                                    => 400,
        'course-dames'                                                     => 400,
        'course-des-peres-meres-noel'                                      => 300,
        'famigros-run-win'                                                 => 300,
        'course-des-enfants-1-tour'                                        => 400,
        'course-des-enfants-2-tours'                                       => 400,
        'course-des-cadets-juniors-3-tours'                                => 300,
        'course-des-cadets-juniors-4-tours'                                => 300,
        'populaires-et-medaille-sportive-sedunoise-walking-nordic-walking' => 400,
        'course-adaptee-personnes-en-situation-d-handicap'                 => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alias et correspondances entre slugs Datasport et codes / noms Run
    |--------------------------------------------------------------------------
    */
    'slug_mappings' => [
        'trail-des-chateaux'                                               => 'DS-TRAIL-20K',
        'trail-des-chatelets'                                              => 'DS-TRAIL-10K',
        'course-hommes'                                                    => 'DS-HOMMES',
        'course-dames'                                                     => 'DS-DAMES',
        'challenge-entreprises'                                            => 'DS-ENTREPRISES',
        'interclasses'                                                     => 'DS-INTERCLASSES',
        'famigros-run-win'                                                 => 'DS-FAMIGROS',
        'course-des-enfants-1-tour'                                        => 'DS-ENF-1T',
        'course-des-enfants-2-tours'                                       => 'DS-ENF-2T',
        'course-des-cadets-juniors-3-tours'                                => 'DS-CAD-3T',
        'course-des-cadets-juniors-4-tours'                                => 'DS-CAD-4T',
        'course-des-peres-meres-noel'                                      => 'DS-NOEL',
        'populaires-et-medaille-sportive-sedunoise-walking-nordic-walking' => 'DS-POP',
        'course-adaptee-personnes-en-situation-d-handicap'                 => 'DS-ADAPTEE',
    ],

];
