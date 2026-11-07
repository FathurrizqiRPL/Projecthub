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
