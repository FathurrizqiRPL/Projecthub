<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\PMController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
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
            [AdminController::class, 'dashboard']
        )->name('dashboard');

        /*
        User Management
        */

        Route::get(
            'users',
            [AdminController::class, 'index']
        )->name('users.index');

        Route::get(
            'users/create',
            [AdminController::class, 'create']
        )->name('users.create');

        Route::post(
            'users',
            [AdminController::class, 'store']
        )->name('users.store');

        Route::get(
            'users/{user}',
            [AdminController::class, 'show']
        )->name('users.show');

        Route::get(
            'users/{user}/edit',
            [AdminController::class, 'edit']
        )->name('users.edit');

        Route::patch(
            'users/{user}',
            [AdminController::class, 'update']
        )->name('users.update');

        Route::patch(
            'users/{user}/status',
            [AdminController::class, 'toggleStatus']
        )->name('users.status');

        Route::patch(
            'users/{user}/reset-password',
            [AdminController::class, 'resetPassword']
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
            [PMController::class, 'dashboard']
        )->name('dashboard');

        Route::get('projects', [PMController::class, 'projectsIndex'])
            ->name('projects.index');

        Route::get(
            'reports/projects.pdf',
            [PMController::class, 'exportProjectsReport']
        )->name('reports.projects.pdf');

        Route::get('projects/create', [PMController::class, 'projectsCreate'])
            ->name('projects.create');

        Route::post('projects', [PMController::class, 'projectsStore'])
            ->name('projects.store');

        Route::get('projects/{project}', [PMController::class, 'projectsShow'])
            ->name('projects.show');

        Route::get('projects/{project}/edit', [PMController::class, 'projectsEdit'])
            ->name('projects.edit');

        Route::match(['put', 'patch'], 'projects/{project}', [PMController::class, 'projectsUpdate'])
            ->name('projects.update');

        Route::delete('projects/{project}', [PMController::class, 'projectsDestroy'])
            ->name('projects.destroy');

        Route::get(
            'tasks',
            [PMController::class, 'allTasks']
        )->name('tasks.index');

        Route::get(
            'projects/{project}/workflow',
            [PMController::class, 'workflowEdit']
        )->name('projects.workflow.edit');

        Route::post(
            'projects/{project}/workflow/maintenance',
            [PMController::class, 'addMaintenance']
        )->name('projects.workflow.maintenance');

        Route::put(
            'projects/{project}/workflow',
            [PMController::class, 'workflowUpdate']
        )->name('projects.workflow.update');

        Route::get(
            'projects/{project}/members',
            [PMController::class, 'membersEdit']
        )->name('projects.members.edit');

        Route::put(
            'projects/{project}/members',
            [PMController::class, 'membersUpdate']
        )->name('projects.members.update');

        Route::post(
            'projects/{project}/progress/start',
            [PMController::class, 'progressStart']
        )->name('projects.progress.start');

        Route::post(
            'projects/{project}/progress/next',
            [PMController::class, 'progressNext']
        )->name('projects.progress.next');

        Route::post(
            'projects/{project}/progress/complete',
            [PMController::class, 'progressComplete']
        )->name('projects.progress.complete');

        Route::get(
            'projects/{project}/tasks',
            [PMController::class, 'tasksIndex']
        )->name('projects.tasks.index');

        Route::get(
            'projects/{project}/tasks/create',
            [PMController::class, 'tasksCreate']
        )->name('projects.tasks.create');

        Route::post(
            'projects/{project}/tasks',
            [PMController::class, 'tasksStore']
        )->name('projects.tasks.store');

        Route::get(
            'projects/{project}/tasks/{task}',
            [PMController::class, 'tasksShow']
        )->name('projects.tasks.show');

        Route::get(
            'projects/{project}/tasks/{task}/edit',
            [PMController::class, 'tasksEdit']
        )->name('projects.tasks.edit');

        Route::put(
            'projects/{project}/tasks/{task}',
            [PMController::class, 'tasksUpdate']
        )->name('projects.tasks.update');

        Route::get(
            'projects/{project}/tasks/{task}/subtasks/{subtask}/attachments/{attachment}',
            [PMController::class, 'downloadSubtaskAttachment']
        )->name('projects.tasks.attachments.download');

        Route::get(
            'projects/{project}/tasks/{task}/revisions/{revisionItem}/attachments/{attachment}',
            [PMController::class, 'downloadRevisionAttachment']
        )->name('projects.tasks.revisions.attachments.download');

        Route::post(
            'projects/{project}/tasks/{task}/approve',
            [PMController::class, 'approveTask']
        )->name('projects.tasks.approve');

        Route::post(
            'projects/{project}/tasks/{task}/request-revision',
            [PMController::class, 'requestRevision']
        )->name('projects.tasks.request-revision');

        Route::patch(
            'projects/{project}/tasks/{task}/cancel',
            [PMController::class, 'cancelTask']
        )->name('projects.tasks.cancel');
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
            'projects',
            [KaryawanController::class, 'projectsIndex']
        )->name('projects.index');

        Route::get(
            'projects/{project}',
            [KaryawanController::class, 'projectsShow']
        )->name('projects.show');

        Route::get(
            'tasks',
            [KaryawanController::class, 'tasksIndex']
        )->name('tasks.index');

        Route::get(
            'tasks/{task}',
            [KaryawanController::class, 'tasksShow']
        )->name('tasks.show');

        Route::post(
            'tasks/{task}/start',
            [KaryawanController::class, 'startTask']
        )->name('tasks.start');

        Route::post(
            'tasks/{task}/submit',
            [KaryawanController::class, 'submitTaskForReview']
        )->name('tasks.submit');

        Route::post(
            'tasks/{task}/subtasks',
            [KaryawanController::class, 'storeSubtask']
        )->name('tasks.subtasks.store');

        Route::patch(
            'tasks/{task}/subtasks/{subtask}',
            [KaryawanController::class, 'updateSubtask']
        )->name('tasks.subtasks.update');

        Route::put(
            'tasks/{task}/subtasks/{subtask}/attachments/{attachment}',
            [KaryawanController::class, 'replaceSubtaskAttachment']
        )->name('tasks.subtasks.attachments.update');

        Route::post(
            'tasks/{task}/subtasks/{subtask}/attachments',
            [KaryawanController::class, 'uploadSubtaskAttachment']
        )->name('tasks.subtasks.attachments.store');

        Route::get(
            'tasks/{task}/subtasks/{subtask}/attachments/{attachment}',
            [KaryawanController::class, 'downloadSubtaskAttachment']
        )->name('tasks.subtasks.attachments.download');

        Route::delete(
            'tasks/{task}/subtasks/{subtask}/attachments/{attachment}',
            [KaryawanController::class, 'deleteSubtaskAttachment']
        )->name('tasks.subtasks.attachments.destroy');

        Route::patch(
            'tasks/{task}/revisions/{revisionItem}',
            [KaryawanController::class, 'updateRevisionItem']
        )->name('tasks.revisions.update');
    });

/*
 Profile
*/

Route::middleware([
    'auth',
    'password.changed',
])
    ->group(function (): void {
        Route::get('profile', [ProfileController::class, 'edit'])
            ->name('profile');
        Route::patch('profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.password.update');
        Route::delete('profile', [ProfileController::class, 'destroy'])
            ->name('profile.destroy');
    });

/*
 Authentication Routes
*/

require __DIR__.'/auth.php';
