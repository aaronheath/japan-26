<?php

use App\Models\Project;
use App\Models\User;

test('home page is accessible without auth', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('dashboard requires authentication', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('dashboard is accessible when authenticated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('project page requires authentication', function () {
    $project = Project::factory()->create();

    $response = $this->get(route('project.show', $project));

    $response->assertRedirect(route('login'));
});

test('settings profile requires authentication', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('login'));
});

test('settings password requires authentication', function () {
    $response = $this->get(route('user-password.edit'));

    $response->assertRedirect(route('login'));
});

test('settings appearance requires authentication', function () {
    $response = $this->get(route('appearance.edit'));

    $response->assertRedirect(route('login'));
});

test('settings two factor requires authentication', function () {
    $response = $this->get(route('two-factor.show'));

    $response->assertRedirect(route('login'));
});

test('admin users requires authentication', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

test('admin whitelisted emails requires authentication', function () {
    $response = $this->get(route('admin.whitelisted-emails.index'));

    $response->assertRedirect(route('login'));
});
