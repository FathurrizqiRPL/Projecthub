<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{manager: User, employee: User, project: Project, stage: ProjectStage, task: Task}
 */
function createTaskEditingContext(string $taskStatus = 'pending'): array
{
    $manager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);
    $employee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);
    $project = Project::create([
        'created_by' => $manager->id,
        'name' => 'Editing project',
        'status' => 'active',
    ]);
    $stage = ProjectStage::create([
        'project_id' => $project->id,
        'name' => 'Development',
        'position' => 1,
    ]);
    $project->members()->attach($employee->id);
    $task = Task::create([
        'project_id' => $project->id,
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Original task',
        'description' => 'Original description',
        'status' => $taskStatus,
    ]);
    $task->assignees()->attach($employee->id);

    return compact('manager', 'employee', 'project', 'stage', 'task');
}

it('allows the owning project manager to edit task details and assignees', function () {
    [
        'manager' => $manager,
        'employee' => $employee,
        'project' => $project,
        'stage' => $stage,
        'task' => $task,
    ] = createTaskEditingContext();
    $replacementEmployee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);
    $project->members()->attach($replacementEmployee->id);

    $this->actingAs($manager)
        ->get(route('project-manager.projects.tasks.edit', [$project, $task]))
        ->assertOk()
        ->assertSee('Edit tugas');

    $this->actingAs($manager)
        ->put(route('project-manager.projects.tasks.update', [$project, $task]), [
            'title' => 'Updated task',
            'description' => 'Updated description',
            'project_stage_id' => $stage->id,
            'assignees' => [$replacementEmployee->id],
            'deadline' => '2026-10-30',
        ])
        ->assertRedirect(route('project-manager.projects.tasks.show', [$project, $task]));

    expect($task->fresh()->title)->toBe('Updated task')
        ->and($task->fresh()->description)->toBe('Updated description')
        ->and($task->fresh()->deadline?->format('Y-m-d'))->toBe('2026-10-30')
        ->and($task->fresh()->assignees->modelKeys())->toBe([$replacementEmployee->id])
        ->and($task->fresh()->status)->toBe('pending');
});

it('prevents project managers from editing tasks they do not own or that are final', function () {
    [
        'manager' => $manager,
        'project' => $project,
        'task' => $task,
    ] = createTaskEditingContext();
    $otherManager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);

    $this->actingAs($otherManager)
        ->get(route('project-manager.projects.tasks.edit', [$project, $task]))
        ->assertForbidden();

    $task->update(['status' => 'completed']);

    $this->actingAs($manager)
        ->get(route('project-manager.projects.tasks.edit', [$project, $task]))
        ->assertUnprocessable();
});

it('does not allow a project manager to assign a task to a non-member', function () {
    [
        'manager' => $manager,
        'employee' => $employee,
        'project' => $project,
        'stage' => $stage,
        'task' => $task,
    ] = createTaskEditingContext();
    $outsideEmployee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($manager)
        ->put(route('project-manager.projects.tasks.update', [$project, $task]), [
            'title' => 'Updated task',
            'description' => 'Updated description',
            'project_stage_id' => $stage->id,
            'assignees' => [$outsideEmployee->id],
            'deadline' => null,
        ])
        ->assertSessionHasErrors('assignees.0');

    expect($task->fresh()->title)->toBe('Original task')
        ->and($task->fresh()->assignees->modelKeys())->toBe([$employee->id]);
});

it('allows an assigned employee to edit subtask title and description', function () {
    [
        'employee' => $employee,
        'task' => $task,
    ] = createTaskEditingContext('in_progress');
    $subtask = $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Original subtask',
        'description' => 'Original subtask description',
    ]);

    $this->actingAs($employee)
        ->get(route('employee.tasks.show', $task))
        ->assertOk()
        ->assertSee('Edit judul atau deskripsi');

    $this->actingAs($employee)
        ->patch(route('employee.tasks.subtasks.update', [$task, $subtask]), [
            'title' => 'Updated subtask',
            'description' => 'Updated subtask description',
        ])
        ->assertRedirect(route('employee.tasks.show', $task));

    expect($subtask->fresh()->title)->toBe('Updated subtask')
        ->and($subtask->fresh()->description)->toBe('Updated subtask description');
});

it('does not allow an unassigned employee to edit a subtask', function () {
    [
        'employee' => $employee,
        'task' => $task,
    ] = createTaskEditingContext('in_progress');
    $subtask = $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Original subtask',
    ]);
    $otherEmployee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($otherEmployee)
        ->patch(route('employee.tasks.subtasks.update', [$task, $subtask]), [
            'title' => 'Unauthorized update',
            'description' => 'Unauthorized description',
        ])
        ->assertForbidden();

    expect($subtask->fresh()->title)->toBe('Original subtask');
});

it('still requires the completion report and attachments when completing a subtask', function () {
    Storage::fake('local');
    [
        'employee' => $employee,
        'task' => $task,
    ] = createTaskEditingContext('in_progress');
    $subtask = $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Subtask to complete',
    ]);

    $this->actingAs($employee)
        ->patch(route('employee.tasks.subtasks.update', [$task, $subtask]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors(['completion_note', 'attachments']);

    expect($subtask->fresh()->status)->toBe('pending');

    $this->actingAs($employee)
        ->patch(route('employee.tasks.subtasks.update', [$task, $subtask]), [
            'status' => 'completed',
            'completion_note' => 'Work is complete.',
            'attachments' => [
                UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
            ],
        ])
        ->assertRedirect(route('employee.tasks.show', $task));

    expect($subtask->fresh()->status)->toBe('completed')
        ->and($subtask->fresh()->completion_note)->toBe('Work is complete.')
        ->and($subtask->attachments()->count())->toBe(1);
});

it('replaces an attachment in place for its assigned employee uploader', function () {
    Storage::fake('local');
    [
        'employee' => $employee,
        'task' => $task,
    ] = createTaskEditingContext('in_progress');
    $subtask = $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Subtask with attachment',
    ]);
    $originalPath = 'subtask-attachments/original.pdf';
    Storage::disk('local')->put($originalPath, 'Original attachment');
    $attachment = $subtask->attachments()->create([
        'uploaded_by' => $employee->id,
        'original_name' => 'original.pdf',
        'file_path' => $originalPath,
        'mime_type' => 'application/pdf',
        'file_size' => 20,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.tasks.show', $task))
        ->assertOk()
        ->assertSee('original.pdf')
        ->assertSee(route('employee.tasks.subtasks.attachments.update', [$task, $subtask, $attachment]), false);

    $response = $this->actingAs($employee)
        ->put(route('employee.tasks.subtasks.attachments.update', [$task, $subtask, $attachment]), [
            'attachment' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
        ]);

    $response->assertRedirect(route('employee.tasks.show', $task));

    $attachment->refresh();

    expect($subtask->attachments()->count())->toBe(1)
        ->and($attachment->original_name)->toBe('replacement.pdf')
        ->and($attachment->file_path)->not->toBe($originalPath)
        ->and($attachment->file_size)->toBeGreaterThan(0);

    Storage::disk('local')->assertMissing($originalPath);
    Storage::disk('local')->assertExists($attachment->file_path);
});

it('does not allow an employee to replace another uploader attachment', function () {
    Storage::fake('local');
    [
        'employee' => $employee,
        'task' => $task,
    ] = createTaskEditingContext('in_progress');
    $subtask = $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Subtask with attachment',
    ]);
    $originalPath = 'subtask-attachments/original.pdf';
    Storage::disk('local')->put($originalPath, 'Original attachment');
    $attachment = $subtask->attachments()->create([
        'uploaded_by' => $employee->id,
        'original_name' => 'original.pdf',
        'file_path' => $originalPath,
        'mime_type' => 'application/pdf',
        'file_size' => 20,
    ]);
    $otherEmployee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);
    $task->assignees()->attach($otherEmployee->id);

    $this->actingAs($otherEmployee)
        ->put(route('employee.tasks.subtasks.attachments.update', [$task, $subtask, $attachment]), [
            'attachment' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
        ])
        ->assertForbidden();

    expect($attachment->fresh()->file_path)->toBe($originalPath);
    Storage::disk('local')->assertExists($originalPath);
});
