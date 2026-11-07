<?php

use App\Models\User;

test('administrator can filter users using a regular get form', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);
    $matchingUser = User::factory()->create([
        'name' => 'Matching Employee',
        'role' => 'employee',
        'status' => 'active',
    ]);
    User::factory()->create([
        'name' => 'Different Manager',
        'role' => 'project_manager',
        'status' => 'inactive',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', [
            'search' => 'Matching',
            'role' => 'employee',
            'status' => 'active',
        ]))
        ->assertOk()
        ->assertViewIs('admin.users.index')
        ->assertSee($matchingUser->name)
        ->assertDontSee('Different Manager');
});

test('administrator can set an email when creating and editing a user', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Project Manager',
            'username' => 'new.pm',
            'email' => 'new.pm@example.com',
            'role' => 'project_manager',
        ])
        ->assertRedirect();

    $createdUser = User::query()->where('username', 'new.pm')->firstOrFail();

    expect($createdUser->email)->toBe('new.pm@example.com');

    $this->actingAs($admin)
        ->patch(route('admin.users.update', $createdUser), [
            'name' => $createdUser->name,
            'username' => $createdUser->username,
            'email' => 'updated.pm@example.com',
            'role' => 'project_manager',
        ])
        ->assertRedirect(route('admin.users.show', $createdUser));

    expect($createdUser->fresh()->email)->toBe('updated.pm@example.com');
});
