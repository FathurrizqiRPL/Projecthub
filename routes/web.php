<?php

use App\Http\Controllers\Employee\TaskController as EmployeeTaskController;
use App\Http\Controllers\ProjectManager\ProjectTaskController;
use App\Http\Controllers\ProjectManager\ProjectProgressController;
use App\Http\Controllers\ProjectManager\ProjectMemberController;
use App\Http\Controllers\ProjectManager\ProjectStageController;
use App\Http\Controllers\ProjectManager\ProjectController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProjectManager\DashboardController as ProjectManagerDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return match (Auth::user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'project_manager' => redirect()->route('project-manager.dashboard'),
        'employee' => redirect()->route('employee.tasks.index'),
        default => abort(403),
    };
})->name('home');


/*
Dashboard Redirect
*/

Route::get('dashboard', function () {
    $user = Auth::user();

    if (! $user) {
        return redirect()->route('login');
    }

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),

        'project_manager' => redirect()
            ->route('project-manager.dashboard'),

        'employee' => redirect()
            ->route('employee.tasks.index'),

        default => abort(
            403,
            'Role pengguna tidak dikenali.'
        ),
    };
})
    ->middleware([
        'auth',
        'password.changed',
    ])
    ->name('dashboard');


/*
Admin Routes
*/

Route::middleware([
    'auth',
    'password.changed',
    'role:admin',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get(
            'dashboard',
            [AdminDashboardController::class, 'index']
        )->name('dashboard');


        /*
        User Management
        */

        Route::get(
            'users',
            [UserController::class, 'index']
        )->name('users.index');

        Route::get(
            'users/create',
            [UserController::class, 'create']
        )->name('users.create');

        Route::post(
            'users',
            [UserController::class, 'store']
        )->name('users.store');

        Route::get(
            'users/{user}',
            [UserController::class, 'show']
        )->name('users.show');

        Route::get(
            'users/{user}/edit',
            [UserController::class, 'edit']
        )->name('users.edit');

        Route::patch(
            'users/{user}',
            [UserController::class, 'update']
        )->name('users.update');

        Route::patch(
            'users/{user}/status',
            [UserController::class, 'toggleStatus']
        )->name('users.status');

        Route::patch(
            'users/{user}/reset-password',
            [UserController::class, 'resetPassword']
        )->name('users.reset-password');
    });


/*
Project Manager Routes
*/

Route::middleware([
    'auth',
    'password.changed',
    'role:project_manager',
])
    ->prefix('project-manager')
    ->name('project-manager.')
    ->group(function () {

        Route::get(
            'dashboard',
            [ProjectManagerDashboardController::class, 'index']
        )->name('dashboard');

        Route::resource(
            'projects',
            ProjectController::class
        );

        Route::get(
            'projects/{project}/workflow',
            [ProjectStageController::class, 'edit']
        )->name('projects.workflow.edit');

        Route::put(
            'projects/{project}/workflow',
            [ProjectStageController::class, 'update']
        )->name('projects.workflow.update');

        Route::get(
            'projects/{project}/members',
            [ProjectMemberController::class, 'edit']
        )->name('projects.members.edit');

        Route::put(
            'projects/{project}/members',
            [ProjectMemberController::class, 'update']
        )->name('projects.members.update');

        Route::post(
            'projects/{project}/progress/start',
            [ProjectProgressController::class, 'start']
        )->name('projects.progress.start');

        Route::post(
            'projects/{project}/progress/next',
            [ProjectProgressController::class, 'next']
        )->name('projects.progress.next');

        Route::get(
            'projects/{project}/tasks',
            [ProjectTaskController::class, 'index']
        )->name('projects.tasks.index');

        Route::get(
            'projects/{project}/tasks/create',
            [ProjectTaskController::class, 'create']
        )->name('projects.tasks.create');

        Route::post(
            'projects/{project}/tasks',
            [ProjectTaskController::class, 'store']
        )->name('projects.tasks.store');

        Route::get(
            'projects/{project}/tasks/{task}',
            [ProjectTaskController::class, 'show']
        )->name('projects.tasks.show');

        Route::delete(
            'projects/{project}/tasks/{task}',
            [ProjectTaskController::class, 'destroy']
        )->name('projects.tasks.destroy');
    });


/*
 Employee Routes
*/

Route::middleware([
    'auth',
    'password.changed',
    'role:employee',
])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {

        Route::get(
            'tasks',
            [EmployeeTaskController::class, 'index']
        )->name('tasks.index');

        Route::get(
            'tasks/{task}',
            [EmployeeTaskController::class, 'show']
        )->name('tasks.show');
    });


/*
 Profile
*/

Route::view('profile', 'profile')
    ->middleware([
        'auth',
        'password.changed',
    ])
    ->name('profile');


/*
 Authentication Routes
*/

require __DIR__.'/auth.php';
