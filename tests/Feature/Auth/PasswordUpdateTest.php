<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->from('/profile')
        ->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'password-updated');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->from('/profile')
        ->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
