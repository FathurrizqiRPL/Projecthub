<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get('/login')
        ->assertOk()
        ->assertViewIs('auth.login')
        ->assertSee('Masuk ke ProjectHub')
        ->assertSee('name="username"', false)
        ->assertDontSee('Lupa kata sandi?');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
        'remember' => '1',
    ])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('inactive users cannot authenticate', function () {
    $user = User::factory()->create(['status' => 'inactive']);

    $this->from('/login')->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('users with a temporary password can change it before continuing', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get('/change-password')
        ->assertOk()
        ->assertViewIs('auth.change-password');

    $this->put('/change-password', [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse();
});

test('navigation menu can be rendered with regular browser navigation', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertSee('Keluar');
});

test('users can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('unused Breeze authentication pages are not available', function () {
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password')->assertNotFound();
    $this->get('/reset-password/test-token')->assertNotFound();
    $this->post('/reset-password/test-token')->assertNotFound();
    $this->get('/verify-email')->assertNotFound();
    $this->post('/email/verification-notification')->assertNotFound();
    $this->get('/confirm-password')->assertNotFound();
    $this->post('/confirm-password')->assertNotFound();
});
