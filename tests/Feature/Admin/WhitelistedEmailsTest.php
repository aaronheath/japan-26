<?php

use App\Models\User;
use App\Models\WhitelistedEmail;

test('whitelisted emails page requires authentication', function () {
    $response = $this->get(route('admin.whitelisted-emails.index'));

    $response->assertRedirect(route('login'));
});

test('whitelisted emails page renders', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.whitelisted-emails.index'));

    $response->assertOk();
});

test('can add email to whitelist', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.whitelisted-emails.store'), [
        'email' => 'newuser@example.com',
    ]);

    $response->assertRedirect();
    expect(WhitelistedEmail::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

test('uppercase email is rejected by validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.whitelisted-emails.store'), [
        'email' => 'UPPERCASE@EXAMPLE.COM',
    ]);

    $response->assertSessionHasErrors('email');
});

test('duplicate email is rejected', function () {
    $user = User::factory()->create();
    WhitelistedEmail::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($user)->post(route('admin.whitelisted-emails.store'), [
        'email' => 'existing@example.com',
    ]);

    $response->assertSessionHasErrors('email');
});

test('invalid email is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.whitelisted-emails.store'), [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('can delete email from whitelist', function () {
    $user = User::factory()->create();
    $whitelistedEmail = WhitelistedEmail::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.whitelisted-emails.destroy', $whitelistedEmail));

    $response->assertRedirect();
    expect(WhitelistedEmail::find($whitelistedEmail->id))->toBeNull();
});
