<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertViewIs('auth.forgot-password');
});

test('reset password link can be requested', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertViewIs('auth.reset-password');

        return true;
    });
});

test('password can be reset with a valid token', function () {
    Notification::fake();
    $user = User::factory()->create(['must_change_password' => true]);

    $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->post(route('password.reset.store', $notification->token), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
        expect($user->fresh()->must_change_password)->toBeFalse();

        return true;
    });
});
