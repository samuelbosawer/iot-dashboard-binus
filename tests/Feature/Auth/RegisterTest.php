<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('register screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('new users can register as support and are logged in', function () {
    $response = $this->post(route('register'), [
        'name' => 'Budi',
        'email' => 'budi@binus.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ]);

    $user = User::firstWhere('email', 'budi@binus.ac.id');

    expect($user->role)->toBe(UserRole::Support);
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('registration rejects duplicate emails and mismatched passwords', function () {
    User::factory()->create(['email' => 'budi@binus.ac.id']);

    $this->post(route('register'), [
        'name' => 'Budi',
        'email' => 'budi@binus.ac.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'beda',
    ])->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
});
