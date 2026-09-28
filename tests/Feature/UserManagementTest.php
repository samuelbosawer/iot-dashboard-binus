<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('support users cannot access user management', function () {
    $support = User::factory()->support()->create();

    $this->actingAs($support)->get(route('users.index'))->assertForbidden();
    $this->actingAs($support)->post(route('users.store'), [])->assertForbidden();
});

test('admin can list and search users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Budi Santoso']);
    User::factory()->create(['name' => 'Siti Aminah']);

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'Budi']))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Aminah');
});

test('admin can create a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Operator Baru',
            'email' => 'operator@binus.ac.id',
            'role' => UserRole::Support->value,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'operator@binus.ac.id')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Support)
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

test('admin can update a user without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $originalPassword = $user->password;

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    expect($user->name)->toBe('Nama Baru')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and($user->password)->toBe($originalPassword);
});

test('admin cannot demote themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Support->value,
        ])
        ->assertSessionHasErrors('role');

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('admin can delete another user but not themselves', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
    $this->assertModelMissing($user);

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHas('error');
    $this->assertModelExists($admin);
});
