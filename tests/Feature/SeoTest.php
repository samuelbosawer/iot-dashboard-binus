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
        ->assertSee('<loc>'.route('prediction').'</loc>', false);
});

test('the visitor page has the data table section', function () {
    $this->get(route('visitor'))->assertSee('Tabel Data Sensor')->assertSee('v-table-body');
});
