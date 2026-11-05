<?php

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @return array{manager: User, project: Project, stage: ProjectStage}
 */
function createProjectReportContext(): array
{
    $manager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);
    $project = Project::create([
        'created_by' => $manager->id,
        'name' => 'Project report November',
        'description' => 'Project description',
        'status' => 'active',
    ]);
    $stage = ProjectStage::create([
        'project_id' => $project->id,
        'name' => 'Implementation',
        'position' => 1,
    ]);
    $project->update(['current_stage_id' => $stage->id]);

    return compact('manager', 'project', 'stage');
}

function setProjectReportCreatedAt(Project $project, string $createdAt): void
{
    DB::table('projects')
        ->where('id', $project->id)
        ->update(['created_at' => $createdAt]);
}

it('opens an inline PDF report for all projects owned by the project manager', function () {
    [
        'manager' => $manager,
        'project' => $project,
        'stage' => $stage,
    ] = createProjectReportContext();

    $completedTask = $project->tasks()->create([
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Completed report task',
        'status' => 'completed',
    ]);
    $unfinishedTask = $project->tasks()->create([
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Unfinished report task',
        'status' => 'in_progress',
    ]);
    $project->tasks()->create([
        'project_stage_id' => $stage->id,
        'created_by' => $manager->id,
        'title' => 'Cancelled report task',
        'status' => 'cancelled',
    ]);
    $unfinishedTask->subtasks()->create([
        'created_by' => $manager->id,
        'title' => 'One completed subtask',
        'status' => 'completed',
        'completed_by' => $manager->id,
        'completed_at' => now(),
    ]);

    $otherManager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);
    $otherProject = Project::create([
        'created_by' => $otherManager->id,
        'name' => 'Private project not in report',
        'status' => 'active',
    ]);
    setProjectReportCreatedAt($otherProject, '2026-11-03 12:00:00');

    $response = $this->actingAs($manager)
        ->get(route('project-manager.reports.projects.pdf', ['period' => 'all']));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename=laporan-proyek-semua-periode.pdf');

    $pdfContent = $response->getContent();
    preg_match('/\/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdfContent, $pageDimensions);

    expect($pdfContent)
        ->toStartWith('%PDF-')
        ->not->toContain('/Subtype /Image');

    expect(count($pageDimensions))->toBe(3);
    expect((float) $pageDimensions[1])->toBeLessThan((float) $pageDimensions[2]);
});

it('filters monthly project reports by project creation date', function () {
    [
        'manager' => $manager,
        'project' => $novemberProject,
    ] = createProjectReportContext();
    $octoberProject = Project::create([
        'created_by' => $manager->id,
        'name' => 'Project report October',
        'status' => 'draft',
    ]);
    $otherManager = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'project_manager',
        'must_change_password' => false,
    ]);
    $otherManagerProject = Project::create([
        'created_by' => $otherManager->id,
        'name' => 'Another manager October project',
        'status' => 'active',
    ]);
    setProjectReportCreatedAt($novemberProject, '2026-11-02 12:00:00');
    setProjectReportCreatedAt($octoberProject, '2026-10-25 12:00:00');
    setProjectReportCreatedAt($otherManagerProject, '2026-11-01 12:00:00');

    $response = $this->actingAs($manager)
        ->get(route('project-manager.reports.projects.pdf', [
            'period' => 'month',
            'month' => '2026-11',
        ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename=laporan-proyek-2026-11.pdf');

    expect($response->getContent())
        ->toStartWith('%PDF-')
        ->not->toContain('/Subtype /Image');
});

it('shows project report exports in the project manager dashboard', function () {
    ['manager' => $manager] = createProjectReportContext();

    $this->actingAs($manager)
        ->get(route('project-manager.dashboard'))
        ->assertOk()
        ->assertSee('Ekspor laporan proyek')
        ->assertSee('Semua periode')
        ->assertSee('Per bulan')
        ->assertSee(route('project-manager.reports.projects.pdf', ['period' => 'all']), false)
        ->assertSee('target="_blank"', false);
});

it('requires a valid month for monthly PDF reports', function () {
    ['manager' => $manager] = createProjectReportContext();

    $this->actingAs($manager)
        ->get(route('project-manager.reports.projects.pdf', [
            'period' => 'month',
            'month' => 'invalid',
        ]))
        ->assertSessionHasErrors('month');

    $this->actingAs($manager)
        ->get(route('project-manager.reports.projects.pdf', ['period' => 'month']))
        ->assertSessionHasErrors('month');
});

it('restricts PDF project reports to project managers', function () {
    $employee = User::factory()->create([
        'username' => fake()->unique()->userName(),
        'role' => 'employee',
        'must_change_password' => false,
    ]);

    $this->actingAs($employee)
        ->get(route('project-manager.reports.projects.pdf', ['period' => 'all']))
        ->assertForbidden();
});
