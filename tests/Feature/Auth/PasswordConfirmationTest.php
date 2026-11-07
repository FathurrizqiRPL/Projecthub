<?php

namespace Tests\Feature\Auth;

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/confirm-password')
        ->assertOk()
        ->assertViewIs('auth.confirm-password');
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/confirm-password', ['password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});

test('password is not confirmed with an invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/confirm-password')
        ->post('/confirm-password', ['password' => 'wrong-password'])
        ->assertRedirect('/confirm-password')
        ->assertSessionHasErrors('password');
});
