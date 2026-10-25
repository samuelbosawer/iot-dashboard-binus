<?php

use App\Http\Controllers\AppDownloadController;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const BROWSER_USER_AGENT = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36';
const MOBILE_APP_USER_AGENT = 'Mozilla/5.0 (Linux; Android 14; Pixel 8; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/129.0 Mobile Safari/537.36 median';

test('web browsers see the android app download page', function () {
    $this->withHeader('User-Agent', BROWSER_USER_AGENT)
        ->get(route('app-download'))
        ->assertOk()
        ->assertSee('Monitoring Tanaman Realtime')
        ->assertSee('Download APK')
        ->assertSee(route('app-download.file'))
        ->assertSee('data-bottom-nav', false);
});

test('web browsers download the apk with the android package content type', function () {
    $response = $this->withHeader('User-Agent', BROWSER_USER_AGENT)
        ->get(route('app-download.file'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
        ->assertDownload(AppDownloadController::APK_FILE);

    expect($response->baseResponse->getFile()->getPathname())->toBe(public_path(AppDownloadController::APK_FILE));
});

test('the android app is sent back to the home page instead of the download page', function (string $routeName) {
    $this->withHeader('User-Agent', MOBILE_APP_USER_AGENT)
        ->get(route($routeName))
        ->assertRedirect(route('visitor'));
})->with(['app-download', 'app-download.file']);

test('the navbar links to the download page in web browsers only', function () {
    $this->withHeader('User-Agent', BROWSER_USER_AGENT)
        ->get(route('prediction'))
        ->assertSee(route('app-download'));

    $this->withHeader('User-Agent', MOBILE_APP_USER_AGENT)
        ->get(route('prediction'))
        ->assertOk()
        ->assertDontSee(route('app-download'));
});
