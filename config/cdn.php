<?php

return [

    'default_edition_id' => env('CDN_DEFAULT_EDITION_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Règlement Course Interclasses (Écoles)
    |--------------------------------------------------------------------------
    | Configuration des critères de conformité par équipe :
    | - min_students : Nombre minimum d'élèves par classe (défaut: 8)
    | - min_girls    : Nombre minimum de filles par classe (défaut: 3)
    | - min_level    : Degré scolaire minimum admis (défaut: 3H)
    | - max_level    : Degré scolaire maximum admis (défaut: 8H)
    */
    'interclasses' => [
        'min_students' => (int) env('CDN_INTERCLASSES_MIN_STUDENTS', 8),
        'min_girls'    => (int) env('CDN_INTERCLASSES_MIN_GIRLS', 3),
    ],

];

