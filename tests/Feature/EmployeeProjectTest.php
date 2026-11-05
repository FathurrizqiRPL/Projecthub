<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\Task;
use App\Models\User;

/**
 * @return array{manager: User, employee: User, teammate: User, project: Project, firstStage: ProjectStage, secondStage: ProjectStage, myTask: Task, teammateTask: Task}
 */
function createEmployeeProjectContext(): array
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
    $teammate = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);
    $project = Project::create([
        'created_by' => $manager->id,
        'name' => 'Employee visible project',
        'description' => 'Project overview',
        'status' => 'active',
    ]);
    $firstStage = ProjectStage::create([
        'project_id' => $project->id,
        'name' => 'Planning',
        'position' => 1,
    ]);
    $secondStage = ProjectStage::create([
        'project_id' => $project->id,
        'name' => 'Development',
        'position' => 2,
    ]);
    $project->update(['current_stage_id' => $firstStage->id]);
    $myTask = $project->tasks()->create([
        'project_stage_id' => $firstStage->id,
        'created_by' => $manager->id,
        'title' => 'My assigned task',
        'description' => 'Work assigned to me',
        'status' => 'pending',
    ]);
    $myTask->assignees()->attach($employee->id);
    $teammateTask = $project->tasks()->create([
        'project_stage_id' => $firstStage->id,
        'created_by' => $manager->id,
        'title' => 'Teammate assigned task',
        'description' => 'Work assigned to my teammate',
        'status' => 'in_progress',
    ]);
    $teammateTask->assignees()->attach($teammate->id);

    return compact(
        'manager',
        'employee',
        'teammate',
        'project',
        'firstStage',
        'secondStage',
        'myTask',
        'teammateTask'
    );
}

it('lists only projects where the employee has an assigned task', function () {
    [
        'employee' => $employee,
        'project' => $project,
    ] = createEmployeeProjectContext();
    $unassignedProject = Project::create([
        'created_by' => $project->created_by,
        'name' => 'Project not assigned to employee',
        'status' => 'active',
    ]);
    ProjectStage::create([
        'project_id' => $unassignedProject->id,
        'name' => 'Planning',
        'position' => 1,
    ]);

    $this->actingAs($employee)
        ->get(route('employee.projects.index'))
        ->assertOk()
        ->assertSee('Proyek saya')
        ->assertSee($project->name)
        ->assertSee('1 tugas untuk Anda')
        ->assertDontSee($unassignedProject->name)
        ->assertSee(route('employee.projects.show', $project), false);
});

it('searches assigned projects by name or description without exposing other projects', function () {
    [
        'employee' => $employee,
        'project' => $assignedProject,
        'manager' => $manager,
    ] = createEmployeeProjectContext();
    $assignedByDescription = Project::create([
        'created_by' => $manager->id,
        'name' => 'Another assigned project',
        'description' => 'Contains the unique keyword',
        'status' => 'active',
    ]);
    $stage = ProjectStage::create([
        'project_id' => $assignedByDescription->id,
        'name' => 'Planning',
        'position' => 1,
    ]);
    $task = $assignedByDescription->tasks()->create([
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Assigned work',
        'status' => 'pending',
    ]);
    $task->assignees()->attach($employee->id);

    $unassignedProject = Project::create([
        'created_by' => $manager->id,
        'name' => 'Unique keyword unassigned project',
        'description' => 'This project is not assigned to the employee',
        'status' => 'active',
    ]);

    $this->actingAs($employee)
        ->get(route('employee.projects.index', ['search' => 'unique keyword']))
        ->assertOk()
        ->assertSee($assignedByDescription->name)
        ->assertDontSee($assignedProject->name)
        ->assertDontSee($unassignedProject->name)
        ->assertSee('value="unique keyword"', false);

    $this->actingAs($employee)
        ->get(route('employee.projects.index', ['search' => 'Employee visible']))
        ->assertOk()
        ->assertSee($assignedProject->name)
        ->assertDontSee($assignedByDescription->name);

    $this->actingAs($employee)
        ->get(route('employee.projects.index', ['search' => 'no matching project']))
        ->assertOk()
        ->assertSee('Proyek tidak ditemukan');
});

it('shows project stages and groups assigned and teammate tasks separately', function () {
    [
        'employee' => $employee,
        'project' => $project,
        'firstStage' => $firstStage,
        'secondStage' => $secondStage,
        'myTask' => $myTask,
        'teammateTask' => $teammateTask,
        'teammate' => $teammate,
    ] = createEmployeeProjectContext();
    $secondStageTask = $project->tasks()->create([
        'project_stage_id' => $secondStage->id,
        'created_by' => $project->created_by,
        'title' => 'Second stage task',
        'status' => 'pending',
    ]);
    $secondStageTask->assignees()->attach($employee->id);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', $project))
        ->assertOk()
        ->assertSee('Workflow proyek')
        ->assertSee('Tugas Saya')
        ->assertSee('Tugas Rekan Tim')
        ->assertSee($myTask->title)
        ->assertSee($teammateTask->title)
        ->assertSee($teammate->name)
        ->assertSee('Lihat detail saja')
        ->assertSee(route('employee.projects.show', [
            'project' => $project,
            'stage' => $secondStage->id,
        ]), false);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', [
            'project' => $project,
            'stage' => $secondStage->id,
        ]))
        ->assertOk()
        ->assertSee($secondStage->name)
        ->assertSee($secondStageTask->title)
        ->assertDontSee($myTask->title)
        ->assertDontSee($teammateTask->title);

    $this->actingAs($employee)
        ->get(route('employee.projects.show', [
            'project' => $project,
            'stage' => 0,
        ]))
        ->assertNotFound();
});

it('allows viewing a teammate task but never allows the employee to work on it', function () {
    [
        'employee' => $employee,
        'project' => $project,
        'teammateTask' => $teammateTask,
    ] = createEmployeeProjectContext();

    $this->actingAs($employee)
        ->get(route('employee.tasks.show', $teammateTask))
        ->assertOk()
        ->assertSee('Hanya lihat')
        ->assertSee('Anda tidak ditugaskan ke tugas ini.')
        ->assertDontSee('Mulai Mengerjakan')
        ->assertSee(route('employee.projects.show', [
            'project' => $project,
            'stage' => $teammateTask->project_stage_id,
        ]), false);

    $this->actingAs($employee)
        ->post(route('employee.tasks.start', $teammateTask))
        ->assertForbidden();

    expect($teammateTask->fresh()->status)->toBe('in_progress');
});

it('does not expose project tasks to employees outside the project', function () {
    [
        'project' => $project,
        'teammateTask' => $teammateTask,
    ] = createEmployeeProjectContext();
    $outsider = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($outsider)
        ->get(route('employee.projects.show', $project))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get(route('employee.tasks.show', $teammateTask))
        ->assertForbidden();
});
