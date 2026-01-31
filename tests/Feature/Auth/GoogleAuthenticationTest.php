<?php

use App\Models\User;
use App\Models\WhitelistedEmail;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

test('redirect to google returns redirect response', function () {
    $response = $this->get(route('google.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('whitelisted user can login with google', function () {
    WhitelistedEmail::factory()->create(['email' => 'test@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Test User');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('google.callback'));

    $this->assertAuthenticated();
    $response->assertRedirect(config('fortify.home'));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->google_id)->toBe('123456789');
    expect($user->name)->toBe('Test User');
});

test('non-whitelisted user is rejected', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('123456789');
    $socialiteUser->shouldReceive('getEmail')->andReturn('notwhitelisted@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('existing password user can link google account', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    WhitelistedEmail::factory()->create(['email' => 'existing@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('987654321');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing User');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('google.callback'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(config('fortify.home'));

    $user->refresh();
    expect($user->google_id)->toBe('987654321');
});

test('existing google user can login again', function () {
    $user = User::factory()->googleUser()->create([
        'email' => 'googleuser@example.com',
    ]);

    WhitelistedEmail::factory()->create(['email' => 'googleuser@example.com']);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($user->google_id);
    $socialiteUser->shouldReceive('getEmail')->andReturn('googleuser@example.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Google User');

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get(route('google.callback'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(config('fortify.home'));
});

test('password login still works', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('logout works after google login', function () {
    $user = User::factory()->googleUser()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});
