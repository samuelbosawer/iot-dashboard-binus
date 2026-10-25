<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public pages have seo meta tags and the new favicon', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('<meta name="description"', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('favicon.svg')
        ->assertSee('apple-touch-icon.png')
        ->assertDontSee('Binus')
        ->assertDontSee('BINUS');
})->with(['visitor', 'prediction']);

test('admin and auth pages are not indexed and do not mention binus', function () {
    $this->get(route('login'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('og:title')
        ->assertDontSee('Binus');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('BINUS');
});

test('robots.txt blocks private areas and points to the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /dashboard')
        ->assertSee('Sitemap: '.route('sitemap'));
});

test('sitemap lists the public pages', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('visitor').'</loc>', false)
        ->assertSee('<loc>'.route('prediction').'</loc>', false)
        ->assertSee('<loc>'.route('app-download').'</loc>', false);
});

test('the visitor page has the data table section', function () {
    $this->get(route('visitor'))->assertSee('Tabel Data Sensor')->assertSee('v-table-body');
});

test('public pages are installable as a progressive web app', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('site.webmanifest')
        ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false)
        ->assertSee('viewport-fit=cover', false)
        ->assertSee('data-pwa-install', false)
        ->assertSee('data-bottom-nav', false);
})->with(['visitor', 'prediction']);

test('the web app manifest has maskable icons and the offline assets exist', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray(['display' => 'standalone', 'scope' => '/'])
        ->and(collect($manifest['icons'])->where('purpose', 'maskable')->pluck('sizes')->all())->toBe(['192x192', '512x512']);

    foreach ([...array_column($manifest['icons'], 'src'), '/sw.js', '/offline.html', '/apple-touch-icon.png'] as $path) {
        expect(public_path(ltrim($path, '/')))->toBeFile();
    }
});
