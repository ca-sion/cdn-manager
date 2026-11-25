<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('robots.txt disallows all crawlers', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /');
});

test('public pages include X-Robots-Tag and meta robots noindex', function () {
    $response = $this->get(route('advertisers.start'));

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('admin login page includes X-Robots-Tag noindex', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
