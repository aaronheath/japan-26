<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('home.redirect'))->assertRedirect(route('login'));
});

test('authenticated users are redirected to project overview', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('home.redirect'))->assertRedirect('/project/1');
});
