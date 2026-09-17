<?php

declare(strict_types=1);

use App\Models\Run;
use App\Models\RunRegistration;
use App\Models\RunRegistrationElement;
use App\Services\DatasportScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('fetches fill rates and returns successful public page with snow blue theme and rounded percentages', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {
                            "title": "Course de Noël et Trail des Châteaux 2026, Sion",
                            "date": "12.12.2026",
                            "location": "Sion",
                            "onreg": {"link": "https://onreg.datasport.com/test"}
                        },
                        "filter": [
                            {
                                "values": [
                                    {"name": "Toutes les épreuves"},
                                    {"name": "Trail des Châteaux", "target": {"slug": "trail-des-chateaux"}},
                                    {"name": "Course Hommes", "target": {"slug": "course-hommes"}}
                                ]
                            }
                        ],
                        "contests": [
                            {
                                "name": "Trail des Châteaux",
                                "lists": [
                                    {"type": "gender", "name": "Femmes", "count.entered": 150},
                                    {"type": "gender", "name": "Hommes", "count.entered": 300}
                                ]
                            }
                        ]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->get('/courses/remplissage');

    $response->assertStatus(200);
    $response->assertSee('Trail des Châteaux');
    $response->assertSee('Course Hommes');
    // 450 sur 500 => 90%
    $response->assertSee('90%');
});

it('marks race as 100% full when 15 or fewer slots remain', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {"title": "Course de Noël"},
                        "filter": [{"values": [{"name": "Trail des Châteaux", "target": {"slug": "trail-des-chateaux"}}]}]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    // Run avec limite de 500 et 490 inscrits (il reste 10 places <= 15)
    Run::factory()->create([
        'name' => 'Trail des Châteaux',
        'datasport_code' => 'DS-TRAIL-20K',
        'registrations_limit' => 500,
        'registrations_number' => 490,
    ]);

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->get('/courses/remplissage');

    $response->assertStatus(200);
    $response->assertSee('100%');
    $response->assertSee('Complet');
});

it('aggregates internal CDN registrations before deadline', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {"title": "Course de Noël"},
                        "filter": [{"values": [{"name": "Challenge Entreprises", "target": {"slug": "challenge-entreprises"}}]}]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    // Création d'une course en BD avec limite 100
    $run = Run::factory()->create([
        'name' => 'Challenge Entreprises',
        'datasport_code' => 'DS-ENTREPRISES',
        'registrations_limit' => 100,
        'registrations_number' => 0,
    ]);

    $registration = RunRegistration::factory()->create();

    // 50 inscrits internes via le formulaire CDN Manager
    for ($i = 0; $i < 50; $i++) {
        RunRegistrationElement::create([
            'run_registration_id' => $registration->id,
            'run_id' => $run->id,
            'first_name' => 'Test'.$i,
            'last_name' => 'Runner'.$i,
            'gender' => 'M',
        ]);
    }

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->get('/courses/remplissage');

    $response->assertStatus(200);
    // 50 inscrits sur 100 places => 50%
    $response->assertSee('50%');
});

it('serves the public page in embed mode without repeating individual register buttons', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {"title": "Course de Noël"},
                        "filter": [{"values": [{"name": "Trail des Châteaux", "target": {"slug": "trail-des-chateaux"}}]}]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->get('/courses/remplissage?embed=1');

    $response->assertStatus(200);
    $response->assertSee('cdn-widget-resize');
});

it('returns valid JSON from the public API endpoint', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {"title": "Course de Noël 2026"},
                        "filter": [{"values": [{"name": "Trail des Châteaux", "target": {"slug": "trail-des-chateaux"}}]}]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->getJson('/api/courses/remplissage');

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'event',
            'courses',
            'last_updated_at',
            'is_fallback',
        ],
    ]);
});

it('renders interclasses levels breakdown (3H to 8H)', function () {
    $mockHtml = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <script id="__NEXT_DATA__" type="application/json">
        {
            "props": {
                "pageProps": {
                    "response": {
                        "header": {"title": "Course de Noël"},
                        "filter": [{"values": [{"name": "Interclasses", "target": {"slug": "interclasses"}}]}]
                    }
                }
            }
        }
        </script>
    </head>
    <body></body>
    </html>
    HTML;

    $run = Run::factory()->create([
        'name' => 'Interclasses',
        'datasport_code' => 'DS-INTERCLASSES',
        'registrations_limit' => 1200,
    ]);

    // Inscrire une classe de 3H avec 10 élèves (sur 200 places max = 5%)
    $schoolReg = RunRegistration::factory()->create([
        'run_registration_type' => 'school',
        'school_class_level' => '3H',
    ]);

    for ($i = 0; $i < 10; $i++) {
        RunRegistrationElement::create([
            'run_registration_id' => $schoolReg->id,
            'run_id' => $run->id,
            'first_name' => 'Eleve'.$i,
            'last_name' => 'Test',
            'gender' => 'F',
        ]);
    }

    Http::fake([
        'https://results.datasport.com/*' => Http::response($mockHtml, 200),
    ]);

    $response = $this->get('/courses/remplissage');

    $response->assertStatus(200);
    $response->assertSee('Interclasses');
    $response->assertSee('3H');
    $response->assertSee('8H');
    $response->assertSee('5%');
});
