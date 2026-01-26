<?php

use App\Models\User;
use App\Models\WhitelistedEmail;

test('user management page requires authentication', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

test('user management page renders with user list', function () {
    $user = User::factory()->create();
    User::factory()->count(2)->create();

    $response = $this->actingAs($user)->get(route('users.index'));

    $response->assertOk();
});

test('can create password user and returns generated password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('users.store'), [
        'email' => 'newpassworduser@example.com',
        'auth_type' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('generated_password');

    $newUser = User::where('email', 'newpassworduser@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->password)->not->toBeNull();
    expect($newUser->google_id)->toBeNull();
});

test('password user password is hashed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('users.store'), [
        'email' => 'hashtest@example.com',
        'auth_type' => 'password',
    ]);

    $generatedPassword = session('generated_password');
    $newUser = User::where('email', 'hashtest@example.com')->first();

    expect(password_verify($generatedPassword, $newUser->password))->toBeTrue();
});

test('can create google user with null password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('users.store'), [
        'email' => 'newgoogleuser@example.com',
        'auth_type' => 'google',
    ]);

    $response->assertRedirect();
    $response->assertSessionMissing('generated_password');

    $newUser = User::where('email', 'newgoogleuser@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->password)->toBeNull();
});

test('creating google user auto-whitelists email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('users.store'), [
        'email' => 'autowhitelist@example.com',
        'auth_type' => 'google',
    ]);

    expect(WhitelistedEmail::where('email', 'autowhitelist@example.com')->exists())->toBeTrue();
});

test('creating google user does not duplicate whitelist entry', function () {
    $user = User::factory()->create();
    WhitelistedEmail::factory()->create(['email' => 'alreadywhitelisted@example.com']);

    $this->actingAs($user)->post(route('users.store'), [
        'email' => 'alreadywhitelisted@example.com',
        'auth_type' => 'google',
    ]);

    expect(WhitelistedEmail::where('email', 'alreadywhitelisted@example.com')->count())->toBe(1);
});

test('duplicate email is rejected', function () {
    $user = User::factory()->create();
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($user)->post(route('users.store'), [
        'email' => 'existing@example.com',
        'auth_type' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
});

test('can delete user', function () {
    $user = User::factory()->create();
    $userToDelete = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('users.destroy', $userToDelete));

    $response->assertRedirect();
    expect(User::find($userToDelete->id))->toBeNull();
});

test('cannot delete own account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('users.destroy', $user));

    $response->assertSessionHasErrors('user');
    expect(User::find($user->id))->not->toBeNull();
});
