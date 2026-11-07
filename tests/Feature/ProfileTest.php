<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewIs('profile')
        ->assertSee('Informasi Profil')
        ->assertSee('Perbarui kata sandi')
        ->assertDontSee('Alamat email Anda belum terverifikasi.')
        ->assertDontSee('verifikasi email');
});

test('profile information can be updated', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('an administrator can delete their account', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete an account', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->from('/profile')
        ->delete('/profile', ['password' => 'wrong-password'])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors('password');

    expect($user->fresh())->not->toBeNull();
});

test('non-administrators cannot delete their accounts through the profile form', function () {
    $user = User::factory()->create([
        'role' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertForbidden();

    expect($user->fresh())->not->toBeNull();
});
