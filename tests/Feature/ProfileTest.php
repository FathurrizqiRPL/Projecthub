<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertViewIs('profile')
        ->assertSee('Informasi Profil')
        ->assertSee('Foto profil')
        ->assertSee('name="photo"', false)
        ->assertSee('enctype="multipart/form-data"', false)
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

test('project managers and employees can upload and replace their profile photos', function (string $role) {
    Storage::fake('public');

    $user = User::factory()->create([
        'role' => $role,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('profile.png'),
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-updated');

    $firstPhotoPath = $user->fresh()->profile_photo;

    expect($firstPhotoPath)->toStartWith('profile-photos/');
    Storage::disk('public')->assertExists($firstPhotoPath);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee(Storage::disk('public')->url($firstPhotoPath), false);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('replacement.webp'),
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-updated');

    $replacementPhotoPath = $user->fresh()->profile_photo;

    expect($replacementPhotoPath)->not->toBe($firstPhotoPath);
    Storage::disk('public')->assertMissing($firstPhotoPath);
    Storage::disk('public')->assertExists($replacementPhotoPath);
})->with(['project_manager', 'employee']);

test('profile photo upload rejects non-image files', function () {
    Storage::fake('public');
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect('/profile')
        ->assertSessionHasErrors('photo');

    expect($user->fresh()->profile_photo)->toBeNull();
    expect(Storage::disk('public')->allFiles('profile-photos'))->toBeEmpty();
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
