<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('admin can authenticate and is redirected to the dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('dashboard'));
});

test('support can authenticate and is redirected to the dashboard', function () {
    $support = User::factory()->support()->create();

    $response = $this->post(route('login'), [
        'email' => $support->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($support);
    $response->assertRedirect(route('dashboard'));
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('authenticated users are redirected away from the login screen', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('dashboard'));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('dashboard shows the authenticated user role', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertSee(UserRole::Admin->label());
});
