<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the visitor page is the home page', function () {
    $this->get('/')->assertOk()->assertSee('Data Sensor Live');
});

test('the old visitor url redirects to the home page', function () {
    $this->get('/visitor')->assertRedirect('/')->assertStatus(301);
});
