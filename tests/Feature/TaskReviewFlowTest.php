<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;

/**
 * @return array{manager: User, employee: User, project: Project, task: Task}
 */
function createTaskReviewContext(string $taskStatus = 'in_progress'): array
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
        'name' => 'Review project',
        'status' => 'active',
    ]);
    $stage = ProjectStage::create([
        'project_id' => $project->id,
        'name' => 'Development',
        'position' => 1,
    ]);
    $task = Task::create([
        'project_id' => $project->id,
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Review task',
        'status' => $taskStatus,
    ]);
    $task->assignees()->attach($employee);

    return compact('manager', 'employee', 'project', 'task');
}

function addCompletedReviewSubtask(Task $task, User $employee): Subtask
{
    return $task->subtasks()->create([
        'created_by' => $employee->id,
        'completed_by' => $employee->id,
        'title' => 'Completed work',
        'status' => 'completed',
        'completed_at' => now(),
    ]);
}

it('requires at least one subtask and all subtasks completed before submitting', function () {
    ['employee' => $employee, 'task' => $task] = createTaskReviewContext();

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertUnprocessable();

    expect($task->fresh()->status)->toBe('in_progress');

    $task->subtasks()->create([
        'created_by' => $employee->id,
        'title' => 'Incomplete work',
    ]);

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertUnprocessable();

    expect($task->fresh()->status)->toBe('in_progress');
});

it('submits a task when every subtask is completed', function () {
    ['employee' => $employee, 'task' => $task] = createTaskReviewContext();
    addCompletedReviewSubtask($task, $employee);

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertRedirect(route('employee.tasks.show', $task));

    expect($task->fresh()->status)->toBe('waiting_review')
        ->and($task->fresh()->review_cycle)->toBe(1);
});

it('allows an assigned employee to start a pending task', function () {
    ['employee' => $employee, 'task' => $task] = createTaskReviewContext('pending');

    $this->actingAs($employee)
        ->post(route('employee.tasks.start', $task))
        ->assertRedirect(route('employee.tasks.show', $task));

    expect($task->fresh()->status)->toBe('in_progress');
});

it('keeps revision history through follow-up cycles until approval', function () {
    [
        'manager' => $manager,
        'employee' => $employee,
        'project' => $project,
        'task' => $task,
    ] = createTaskReviewContext();
    addCompletedReviewSubtask($task, $employee);

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertRedirect();

    $this->actingAs($manager)
        ->get(route('project-manager.projects.tasks.reviews', $project))
        ->assertOk()
        ->assertSee('Review task');

    $this->actingAs($manager)
        ->get(route('project-manager.projects.tasks.show', [$project, $task]))
        ->assertOk();

    $this->actingAs($manager)
        ->post(route('project-manager.projects.tasks.request-revision', [$project, $task]), [
            'revision_notes' => "Fix validation\nImprove mobile layout",
        ])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('revision')
        ->and($task->revisionItems()->where('cycle', 1)->count())->toBe(2);

    $this->actingAs($employee)
        ->get(route('employee.tasks.show', $task))
        ->assertOk()
        ->assertSee('Fix validation')
        ->assertSee('Improve mobile layout');

    $revisionItems = $task->revisionItems()->where('cycle', 1)->get();

    foreach ($revisionItems as $revisionItem) {
        $this->actingAs($employee)
            ->patch(route('employee.tasks.revisions.update', [$task, $revisionItem]), [
                'status' => 'resolved',
            ])
            ->assertRedirect();
    }

    expect($task->fresh()->status)->toBe('in_progress');

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('project-manager.projects.tasks.request-revision', [$project, $task]), [
            'revision_notes' => 'Adjust error messages',
        ])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('revision')
        ->and($task->revisionItems()->where('cycle', 1)->count())->toBe(2)
        ->and($task->revisionItems()->where('cycle', 2)->count())->toBe(1);

    $latestRevision = $task->revisionItems()
        ->where('cycle', 2)
        ->firstOrFail();

    $this->actingAs($employee)
        ->patch(route('employee.tasks.revisions.update', [$task, $latestRevision]), [
            'status' => 'resolved',
        ])
        ->assertRedirect();

    $this->actingAs($employee)
        ->post(route('employee.tasks.submit', $task))
        ->assertRedirect();

    $this->actingAs($manager)
        ->post(route('project-manager.projects.tasks.approve', [$project, $task]))
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('completed')
        ->and($task->revisionItems()->count())->toBe(3);
});

it('does not allow a manager outside the project to review its task', function () {
    ['project' => $project, 'task' => $task] = createTaskReviewContext('waiting_review');
    $otherManager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);

    $this->actingAs($otherManager)
        ->post(route('project-manager.projects.tasks.approve', [$project, $task]))
        ->assertForbidden();

    expect($task->fresh()->status)->toBe('waiting_review');
});
