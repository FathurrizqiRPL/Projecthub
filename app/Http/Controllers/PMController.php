<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectCompletionException;
use App\Models\RevisionAttachment;
use App\Models\RevisionItem;
use App\Models\Subtask;
use App\Models\SubtaskAttachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PMController extends Controller
{
    public function dashboard(): View
    {
        $projects = Project::query()
            ->where('created_by', Auth::id());

        $totalProjects = (clone $projects)->count();

        $activeProjects = (clone $projects)
            ->where('status', 'active')
            ->count();

        $managerTasks = Task::query()
            ->whereHas('project', fn ($query) => $query
                ->where('created_by', Auth::id()));

        $ongoingTasks = (clone $managerTasks)
            ->whereIn('status', [
                'pending',
                'in_progress',
                'waiting_review',
                'revision',
            ])
            ->count();

        $completedTasks = (clone $managerTasks)
            ->where('status', 'completed')
            ->count();

        $recentProjects = (clone $projects)
            ->latest()
            ->take(5)
            ->get();

        return view('project-manager.dashboard', compact(
            'totalProjects',
            'activeProjects',
            'ongoingTasks',
            'completedTasks',
            'recentProjects'
        ));
    }

    public function projectsIndex(): View
    {
        $projects = Project::query()
            ->where('created_by', Auth::id())
            ->with(['stages', 'currentStage'])
            ->latest()
            ->paginate(10);

        return view(
            'project-manager.projects.index',
            compact('projects')
        );
    }

    public function projectsCreate(): View
    {
        return view('project-manager.projects.create');
    }

    public function projectsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'deadline' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                'in:draft,active,cancelled',
            ],

            'workflow_type' => [
                'required',
                'in:default,custom',
            ],

            'stages' => [
                'required_if:workflow_type,custom',
                'nullable',
                'array',
                'min:1',
            ],

            'stages.*' => [
                'nullable',
                'string',
                'max:100',
                'distinct',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $project = Project::create([
                'created_by' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'status' => $validated['status'],
            ]);

            $stages = $validated['workflow_type'] === 'default'
                ? [
                    'Perencanaan',
                    'Development',
                    'Testing',
                    'Deployment',
                ]
                : collect($validated['stages'] ?? [])
                    ->map(fn ($stage) => trim($stage))
                    ->filter()
                    ->values()
                    ->all();

            foreach ($stages as $index => $stage) {
                $project->stages()->create([
                    'name' => $stage,
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route('project-manager.projects.index')
            ->with(
                'success',
                'Project berhasil dibuat.'
            );
    }

    public function projectsShow(Project $project): View
    {
        $this->authorizeProject($project);

        $project->load([
            'stages',
            'members',
            'currentStage',
            'completionExceptions.waivingManager',
        ]);

        $incompleteTasks = $project->tasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('stage')
            ->orderBy('project_stage_id')
            ->orderBy('title')
            ->get();
        $projectTaskCount = $project->tasks()->count();
        $incompleteProjectTaskCount = $incompleteTasks->count();
        $currentStageTaskCount = $project->currentStage
            ? $project->tasks()
                ->where('project_stage_id', $project->current_stage_id)
                ->count()
            : 0;
        $incompleteCurrentStageTaskCount = $project->currentStage
            ? $project->tasks()
                ->where('project_stage_id', $project->current_stage_id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count()
            : 0;
        $projectProgress = $project->progress;
        $hasMaintenanceStage = $project->stages->contains(
            fn ($stage) => mb_strtolower(trim($stage->name)) === 'maintenance'
        );

        return view(
            'project-manager.projects.show',
            compact(
                'project',
                'projectTaskCount',
                'incompleteProjectTaskCount',
                'currentStageTaskCount',
                'incompleteCurrentStageTaskCount',
                'projectProgress',
                'incompleteTasks',
                'hasMaintenanceStage'
            )
        );
    }

    public function projectsEdit(Project $project): View
    {
        $this->authorizeProject($project);

        return view(
            'project-manager.projects.edit',
            compact('project')
        );
    }

    public function projectsUpdate(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $allowedStatuses = match (true) {
            $project->status === 'completed' => ['completed'],
            $project->current_stage_id !== null => ['active', 'cancelled'],
            default => ['draft', 'active', 'cancelled'],
        };

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'deadline' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'status' => [
                'required',
                Rule::in($allowedStatuses),
            ],
        ]);

        $project->update($validated);

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Project berhasil diperbarui.'
            );
    }

    public function projectsDestroy(
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $project->delete();

        return redirect()
            ->route('project-manager.projects.index')
            ->with(
                'success',
                'Project berhasil dihapus.'
            );
    }

    private function authorizeProject(
        Project $project
    ): void {
        abort_unless(
            (int) $project->created_by === (int) Auth::id(),
            403
        );
    }

    public function membersEdit(Project $project): View
    {
        $this->authorizeProject($project);

        $employees = User::query()
            ->where('role', 'employee')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $selectedMembers = $project
            ->members()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view(
            'project-manager.projects.members',
            compact(
                'project',
                'employees',
                'selectedMembers'
            )
        );
    }

    public function membersUpdate(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'members' => [
                'nullable',
                'array',
            ],

            'members.*' => [
                'integer',
                'distinct',

                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('role', 'employee')
                            ->where('status', 'active')
                    ),
            ],
        ]);

        $project->members()->sync(
            $validated['members'] ?? []
        );

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Anggota project berhasil diperbarui.'
            );
    }

    public function progressStart(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        abort_unless(
            $project->status === 'active',
            422,
            'Project harus berstatus aktif.'
        );

        abort_if(
            $project->current_stage_id !== null,
            422,
            'Project sudah memiliki stage aktif.'
        );

        $firstStage = $project->stages()
            ->orderBy('position')
            ->first();

        abort_if(
            ! $firstStage,
            422,
            'Project belum memiliki workflow.'
        );

        $project->update([
            'current_stage_id' => $firstStage->id,
        ]);

        return back()->with(
            'success',
            "Project berhasil dimulai pada tahap {$firstStage->name}."
        );
    }

    public function progressNext(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $successMessage = DB::transaction(function () use ($project): string {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active',
                422,
                'Project harus berstatus aktif.'
            );

            abort_if(
                ! $project->currentStage,
                422,
                'Project belum memiliki stage aktif.'
            );

            $currentStage = $project->currentStage;
            $currentStageTasks = $project->tasks()
                ->where('project_stage_id', $currentStage->id);
            $stageTaskCount = (clone $currentStageTasks)->count();
            $incompleteCurrentStageTaskCount = (clone $currentStageTasks)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            abort_if(
                $stageTaskCount === 0,
                422,
                'Stage ini harus memiliki minimal satu task sebelum dapat diselesaikan.'
            );

            abort_if(
                $incompleteCurrentStageTaskCount > 0,
                422,
                "Selesaikan semua task pada stage {$currentStage->name} sebelum melanjutkan."
            );

            $nextStage = $project->stages()
                ->where('position', '>', $currentStage->position)
                ->orderBy('position')
                ->first();

            if ($nextStage) {
                $currentStage->update([
                    'completed_at' => now(),
                ]);

                $project->update([
                    'current_stage_id' => $nextStage->id,
                ]);

                return "Tahap {$currentStage->name} selesai. Project sekarang masuk ke tahap {$nextStage->name}.";
            }

            abort(422, 'Gunakan aksi Selesaikan Project untuk menutup tahap terakhir.');
        });

        return back()->with('success', $successMessage);
    }

    public function progressComplete(Request $request, Project $project): RedirectResponse
    {
        $successMessage = DB::transaction(function () use ($project, $request): string {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active' && $project->currentStage,
                422,
                'Project aktif dengan stage berjalan diperlukan untuk menyelesaikan project.'
            );

            $currentStage = $project->currentStage;
            $hasNextStage = $project->stages()
                ->where('position', '>', $currentStage->position)
                ->exists();

            abort_if(
                $hasNextStage,
                422,
                'Selesaikan stage berikutnya sebelum menutup project.'
            );

            $projectTasks = $project->tasks()
                ->with('stage')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_if(
                $projectTasks->isEmpty(),
                422,
                'Project harus memiliki minimal satu task sebelum dapat diselesaikan.'
            );

            $incompleteTasks = $projectTasks
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->sortBy('project_stage_id')
                ->values();

            if ($incompleteTasks->isNotEmpty()) {
                $submittedIncompleteTaskIds = collect($request->input('incomplete_task_ids', []))
                    ->map(fn ($taskId) => (int) $taskId)
                    ->sort()
                    ->values();
                $currentIncompleteTaskIds = $incompleteTasks
                    ->pluck('id')
                    ->map(fn ($taskId) => (int) $taskId)
                    ->sort()
                    ->values();

                abort_unless(
                    $submittedIncompleteTaskIds->all() === $currentIncompleteTaskIds->all(),
                    409,
                    'Daftar task berubah sejak modal dibuka. Muat ulang halaman lalu tinjau kembali.'
                );

                $request->merge([
                    'completion_reason' => trim((string) $request->input('completion_reason')),
                ]);

                $validated = $request->validate([
                    'confirm_incomplete_tasks' => ['required', 'accepted'],
                    'completion_reason' => ['required', 'string', 'max:1000'],
                ], [
                    'confirm_incomplete_tasks.required' => 'Konfirmasikan pengecualian sebelum menutup project.',
                    'completion_reason.required' => 'Alasan pengecualian wajib diisi.',
                ]);

                $reason = $validated['completion_reason'];

                foreach ($incompleteTasks as $task) {
                    ProjectCompletionException::query()->create([
                        'project_id' => $project->id,
                        'task_id' => $task->id,
                        'waived_by' => Auth::id(),
                        'stage_name' => $task->stage?->name ?? 'Stage tidak tersedia',
                        'task_title' => $task->title,
                        'task_status' => $task->status,
                        'reason' => $reason,
                    ]);
                }
            }

            $currentStage->update([
                'completed_at' => now(),
            ]);
            $project->update([
                'status' => 'completed',
            ]);

            return $incompleteTasks->isEmpty()
                ? 'Semua task selesai. Project berhasil diselesaikan.'
                : 'Project diselesaikan. Task yang dikecualikan tercatat pada detail project.';
        });

        return back()->with('success', $successMessage);
    }

    public function addMaintenance(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        DB::transaction(function () use ($project): void {
            $project = Project::query()
                ->lockForUpdate()
                ->with('currentStage')
                ->findOrFail($project->id);

            $this->authorizeProject($project);

            abort_unless(
                $project->status === 'active' && $project->currentStage,
                422,
                'Maintenance hanya dapat ditambahkan pada project aktif yang sudah dimulai.'
            );

            $hasNextStage = $project->stages()
                ->where('position', '>', $project->currentStage->position)
                ->exists();

            abort_if(
                $hasNextStage,
                422,
                'Tahap Maintenance hanya dapat ditambahkan setelah tahap terakhir workflow.'
            );

            $hasMaintenanceStage = $project->stages()
                ->whereRaw('LOWER(name) = ?', ['maintenance'])
                ->exists();

            abort_if(
                $hasMaintenanceStage,
                422,
                'Tahap Maintenance sudah tersedia pada workflow.'
            );

            $lastPosition = $project->stages()->max('position') ?? 0;

            $project->stages()->create([
                'name' => 'Maintenance',
                'position' => $lastPosition + 1,
            ]);
        });

        return redirect()
            ->route('project-manager.projects.show', $project)
            ->with('success', 'Tahap Maintenance ditambahkan. Buat task maintenance sebelum melanjutkan workflow.');
    }

    public function workflowEdit(Project $project): View
    {
        $this->authorizeProject($project);

        $project->load('stages');

        return view(
            'project-manager.projects.workflow',
            compact('project')
        );
    }

    public function workflowUpdate(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        abort_if(
            $project->status === 'completed',
            422,
            'Workflow project yang sudah selesai tidak dapat diedit.'
        );

        $validated = $request->validate([
            'stages' => [
                'required',
                'array',
                'min:1',
            ],
            'stages.*.id' => [
                'nullable',
                'integer',
            ],
            'stages.*.name' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        DB::transaction(function () use ($project, $validated): void {
            $submittedIds = collect($validated['stages'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            $existingStages = $project
                ->stages()
                ->get();

            if ($project->status === 'active' || $project->current_stage_id !== null) {
                $existingStageIds = $existingStages
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();
                $submittedExistingStageIds = $submittedIds
                    ->filter(fn ($id) => $existingStageIds->contains($id))
                    ->values();

                abort_unless(
                    $submittedExistingStageIds->all() === $existingStageIds->all(),
                    422,
                    'Stage yang sudah ada tidak dapat dihapus dan urutannya tidak dapat diedit saat project aktif.'
                );
            }

            foreach ($existingStages as $existingStage) {
                if (! $submittedIds->contains($existingStage->id)) {
                    if (
                        (int) $project->current_stage_id ===
                        (int) $existingStage->id
                    ) {
                        abort(
                            422,
                            'Stage yang sedang aktif tidak dapat dihapus.'
                        );
                    }

                    abort_if(
                        $existingStage->tasks()->exists(),
                        422,
                        'Stage yang sudah memiliki task tidak dapat dihapus.'
                    );

                    $existingStage->delete();
                }
            }

            foreach ($validated['stages'] as $index => $stageData) {
                $stageId = $stageData['id'] ?? null;

                if ($stageId) {
                    $stage = $project
                        ->stages()
                        ->whereKey($stageId)
                        ->firstOrFail();

                    $stage->update([
                        'name' => trim($stageData['name']),
                        'position' => $index + 1,
                    ]);

                    continue;
                }

                $project->stages()->create([
                    'name' => trim($stageData['name']),
                    'position' => $index + 1,
                ]);
            }
        });

        return redirect()
            ->route(
                'project-manager.projects.show',
                $project
            )
            ->with(
                'success',
                'Workflow project berhasil diperbarui.'
            );
    }

    public function allTasks(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'in:all,review'],
        ]);

        $search = trim($validated['search'] ?? '');
        $reviewOnly = ($validated['filter'] ?? 'all') === 'review';
        $reviewTaskFilter = fn ($query) => $query
            ->where('status', 'waiting_review')
            ->whereDoesntHave('completionException')
            ->whereHas('project', fn ($projectQuery) => $projectQuery
                ->whereIn('status', ['draft', 'active']));
        $taskFilter = fn ($query) => $reviewOnly
            ? $reviewTaskFilter($query)
            : $query;

        $projects = Project::query()
            ->where('created_by', Auth::id())
            ->when($search !== '', fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%'))
            ->when($reviewOnly, fn ($query) => $query
                ->whereHas('tasks', $taskFilter))
            ->with([
                'tasks' => fn ($query) => $taskFilter($query)
                    ->with([
                        'stage',
                        'assignees',
                        'subtasks',
                        'completionException',
                    ])
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->limit(2),
            ])
            ->withCount([
                'tasks as waiting_review_tasks_count' => $reviewTaskFilter,
            ])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $reviewTaskCount = Project::query()
            ->where('created_by', Auth::id())
            ->whereHas('tasks', $reviewTaskFilter)
            ->withCount([
                'tasks as waiting_review_tasks_count' => $reviewTaskFilter,
            ])
            ->get()
            ->sum('waiting_review_tasks_count');

        return view(
            'project-manager.tasks.all',
            compact(
                'projects',
                'search',
                'reviewOnly',
                'reviewTaskCount'
            )
        );
    }

    public function tasksIndex(Project $project): View
    {
        $this->authorizeProject($project);

        $project->load([
            'stages',
            'currentStage',
            'members',
        ]);

        $selectedStage = request('stage');

        if ($selectedStage) {
            $stage = $project->stages
                ->firstWhere('id', (int) $selectedStage);

            abort_unless($stage, 404);
        } else {
            $stage = $project->currentStage
                ?? $project->stages->first();
        }

        $tasks = $stage
            ? $project->tasks()
                ->where('project_stage_id', $stage->id)
                ->with([
                    'assignees',
                    'stage',
                    'subtasks',
                    'completionException',
                ])
                ->latest()
                ->get()
            : collect();

        return view(
            'project-manager.tasks.index',
            compact('project', 'stage', 'tasks')
        );
    }

    public function tasksCreate(Project $project): View
    {
        $this->authorizeProject($project);

        abort_if(
            in_array(
                $project->status,
                ['completed', 'cancelled']
            ),
            422,
            'Task tidak dapat ditambahkan ke project ini.'
        );

        $project->load([
            'stages',
            'members',
            'currentStage',
        ]);

        return view(
            'project-manager.tasks.create',
            compact('project')
        );
    }

    public function tasksStore(
        Request $request,
        Project $project
    ): RedirectResponse {
        $this->authorizeProject($project);

        abort_if(
            in_array(
                $project->status,
                ['completed', 'cancelled']
            ),
            422,
            'Task tidak dapat ditambahkan ke project ini.'
        );

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'project_stage_id' => [
                'required',
                Rule::exists('project_stages', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'project_id',
                                $project->id
                            )
                    ),
            ],
            'assignees' => [
                'required',
                'array',
                'min:1',
            ],
            'assignees.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('project_members', 'user_id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'project_id',
                                $project->id
                            )
                    ),
            ],
            'deadline' => [
                'nullable',
                'date',
                $project->start_date
                ? 'after_or_equal:'.
                    $project->start_date->format('Y-m-d')
                : null,
                $project->deadline
                ? 'before_or_equal:'.
                    $project->deadline->format('Y-m-d')
                : null,
            ],
        ]);

        $workflowStarted = DB::transaction(function () use (
            $project,
            $validated
        ): ?string {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorizeProject($lockedProject);

            abort_if(
                in_array($lockedProject->status, ['completed', 'cancelled'], true),
                422,
                'Task tidak dapat ditambahkan ke project ini.'
            );

            $stage = $lockedProject->stages()
                ->whereKey($validated['project_stage_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $stage->completed_at !== null,
                422,
                'Task tidak dapat ditambahkan ke stage yang sudah selesai.'
            );

            $task = $lockedProject->tasks()->create([
                'project_stage_id' => $validated['project_stage_id'],
                'created_by' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'deadline' => $validated['deadline'] ?? null,
                'status' => 'pending',
            ]);

            $task->assignees()->sync(
                $validated['assignees']
            );

            $firstStage = $lockedProject->stages()
                ->orderBy('position')
                ->first();
            $startsWorkflow = $firstStage
                && $stage->is($firstStage)
                && $lockedProject->current_stage_id === null;

            if ($startsWorkflow) {
                $lockedProject->update([
                    'status' => 'active',
                    'current_stage_id' => $firstStage->id,
                ]);
            }

            return $startsWorkflow ? $stage->name : null;
        });

        return redirect()
            ->route(
                'project-manager.projects.tasks.index',
                [
                    'project' => $project,
                    'stage' => $validated['project_stage_id'],
                ]
            )
            ->with(
                'success',
                $workflowStarted !== null
                    ? 'Task berhasil dibuat. Workflow project otomatis dimulai pada tahap '.$workflowStarted.'.'
                    : 'Task berhasil dibuat.'
            );
    }

    public function tasksShow(
        Project $project,
        Task $task
    ): View {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);

        $task->load([
            'stage',
            'creator',
            'canceller',
            'assignees',
            'completionException',
            'subtasks' => fn ($query) => $query
                ->with([
                    'creator',
                    'completer',
                    'attachments',
                ])
                ->oldest(),
            'revisionItems' => fn ($query) => $query
                ->with([
                    'creator',
                    'resolver',
                    'attachments',
                ])
                ->orderBy('cycle')
                ->orderBy('id'),
        ]);

        $totalSubtasks = $task->subtasks->count();

        $completedSubtasks = $task->subtasks
            ->where('status', 'completed')
            ->count();
        $canModifyProject = ! in_array($project->status, ['completed', 'cancelled'], true);
        $canEditTask = $canModifyProject
            && ! in_array($task->status, ['completed', 'cancelled'], true);

        return view(
            'project-manager.tasks.show',
            compact(
                'project',
                'task',
                'totalSubtasks',
                'completedSubtasks',
                'canModifyProject',
                'canEditTask'
            )
        );
    }

    public function tasksEdit(
        Project $project,
        Task $task
    ): View {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        $this->ensureProjectCanBeModified($project);
        $this->ensureTaskCanBeEdited($task);

        $project->load(['stages', 'members']);
        $task->load(['assignees', 'stage']);
        $stages = $project->stages
            ->filter(fn ($stage): bool => $stage->completed_at === null || $stage->is($task->stage))
            ->sortBy('position')
            ->values();

        return view(
            'project-manager.tasks.edit',
            compact('project', 'task', 'stages')
        );
    }

    public function tasksUpdate(
        Request $request,
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        $this->ensureProjectCanBeModified($project);
        $this->ensureTaskCanBeEdited($task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_stage_id' => [
                'required',
                'integer',
                Rule::exists('project_stages', 'id')
                    ->where(fn ($query) => $query
                        ->where('project_id', $project->id)),
            ],
            'assignees' => ['required', 'array', 'min:1'],
            'assignees.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('project_members', 'user_id')
                    ->where(fn ($query) => $query
                        ->where('project_id', $project->id)),
            ],
            'deadline' => [
                'nullable',
                'date',
                $project->start_date
                    ? 'after_or_equal:'.$project->start_date->format('Y-m-d')
                    : null,
                $project->deadline
                    ? 'before_or_equal:'.$project->deadline->format('Y-m-d')
                    : null,
            ],
        ]);

        DB::transaction(function () use ($project, $task, $validated): void {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorizeProject($lockedProject);
            $this->ensureProjectCanBeModified($lockedProject);

            $lockedTask = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeTask($lockedProject, $lockedTask);
            $this->ensureTaskCanBeEdited($lockedTask);

            $stage = $lockedProject->stages()
                ->whereKey($validated['project_stage_id'])
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $stage->completed_at !== null
                    && (int) $stage->id !== (int) $lockedTask->project_stage_id,
                422,
                'Task tidak dapat dipindahkan ke tahap yang sudah selesai.'
            );

            $validAssigneeCount = $lockedProject->members()
                ->whereIn('users.id', $validated['assignees'])
                ->count();

            abort_unless(
                $validAssigneeCount === count($validated['assignees']),
                422,
                'Semua anggota tugas harus menjadi anggota project.'
            );

            $lockedTask->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'project_stage_id' => $stage->id,
                'deadline' => $validated['deadline'] ?? null,
            ]);

            $lockedTask->assignees()->sync($validated['assignees']);
        });

        return redirect()
            ->route('project-manager.projects.tasks.show', [$project, $task])
            ->with('success', 'Task berhasil diperbarui.');
    }

    public function downloadSubtaskAttachment(
        Project $project,
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): StreamedResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        abort_unless($subtask->task_id === $task->id, 404);
        abort_unless($attachment->subtask_id === $subtask->id, 404);

        /** @var FilesystemAdapter $localDisk */
        $localDisk = Storage::disk('local');
        abort_unless($localDisk->exists($attachment->file_path), 404);

        return $localDisk->response(
            $attachment->file_path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
            'inline'
        );
    }

    public function downloadRevisionAttachment(
        Project $project,
        Task $task,
        RevisionItem $revisionItem,
        RevisionAttachment $attachment
    ): StreamedResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        abort_unless($revisionItem->task_id === $task->id, 404);
        abort_unless($attachment->revision_item_id === $revisionItem->id, 404);

        /** @var FilesystemAdapter $localDisk */
        $localDisk = Storage::disk('local');
        abort_unless($localDisk->exists($attachment->file_path), 404);

        return $localDisk->response(
            $attachment->file_path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
            'inline'
        );
    }

    public function approveTask(
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        $this->ensureProjectCanBeModified($project);

        DB::transaction(function () use ($project, $task): void {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorizeProject($lockedProject);
            $this->ensureProjectCanBeModified($lockedProject);

            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeTask($lockedProject, $task);

            abort_unless(
                $task->status === 'waiting_review',
                422,
                'Task ini tidak sedang menunggu review.'
            );

            $task->update(['status' => 'completed']);

        });

        return redirect()
            ->route('project-manager.projects.tasks.show', [
                'project' => $project,
                'task' => $task,
            ])
            ->with('success', 'Task berhasil disetujui dan diselesaikan.');
    }

    public function requestRevision(
        Request $request,
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);

        $validated = $request->validate([
            'revision_notes' => ['required', 'string', 'max:5000'],
        ]);

        $revisionNotes = array_values(array_filter(
            array_map(
                'trim',
                preg_split('/\R/u', $validated['revision_notes']) ?: []
            ),
            fn (string $note): bool => $note !== ''
        ));

        $validatedItems = Validator::make(
            ['revision_items' => $revisionNotes],
            [
                'revision_items' => ['required', 'array', 'min:1', 'max:10'],
                'revision_items.*' => ['required', 'string', 'max:1000'],
            ],
            [
                'revision_items.required' => 'Masukkan minimal satu item revisi, satu item per baris.',
                'revision_items.max' => 'Maksimal sepuluh item revisi untuk satu putaran.',
                'revision_items.*.max' => 'Setiap item revisi maksimal 1000 karakter.',
            ]
        )->validate();

        DB::transaction(function () use ($project, $task, $validatedItems): void {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorizeProject($lockedProject);
            $this->ensureProjectCanBeModified($lockedProject);

            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeTask($lockedProject, $task);

            abort_unless(
                $task->status === 'waiting_review',
                422,
                'Revisi hanya dapat diminta saat task menunggu review.'
            );

            $cycle = max(1, $task->review_cycle);

            $task->revisionItems()->createMany(
                array_map(
                    fn (string $note): array => [
                        'cycle' => $cycle,
                        'created_by' => Auth::id(),
                        'note' => $note,
                    ],
                    $validatedItems['revision_items']
                )
            );

            $task->update([
                'status' => 'revision',
                'review_cycle' => $cycle,
            ]);

        });

        return redirect()
            ->route('project-manager.projects.tasks.show', [
                'project' => $project,
                'task' => $task,
            ])
            ->with('success', 'Catatan revisi berhasil dikirim ke employee.');
    }

    public function cancelTask(
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        $this->ensureProjectCanBeModified($project);

        $stageId = DB::transaction(function () use ($project, $task): int {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            $this->authorizeProject($lockedProject);
            $this->ensureProjectCanBeModified($lockedProject);

            $lockedTask = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeTask($lockedProject, $lockedTask);

            abort_unless(
                ! in_array($lockedTask->status, ['completed', 'cancelled'], true),
                422,
                'Task yang sudah selesai atau dibatalkan tidak dapat dibatalkan lagi.'
            );

            $lockedTask->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
            ]);

            return (int) $lockedTask->project_stage_id;
        });

        return redirect()
            ->route(
                'project-manager.projects.tasks.index',
                [
                    'project' => $project,
                    'stage' => $stageId,
                ]
            )
            ->with(
                'success',
                'Task berhasil dibatalkan. Riwayat task tetap tersimpan.'
            );
    }

    private function authorizeTask(
        Project $project,
        Task $task
    ): void {
        abort_unless(
            (int) $task->project_id ===
            (int) $project->id,
            404
        );
    }

    private function ensureTaskCanBeEdited(Task $task): void
    {
        abort_unless(
            ! in_array($task->status, ['completed', 'cancelled'], true),
            422,
            'Task yang sudah selesai atau dibatalkan tidak dapat diedit.'
        );
    }

    private function ensureProjectCanBeModified(Project $project): void
    {
        abort_unless(
            ! in_array($project->status, ['completed', 'cancelled'], true),
            422,
            'Task tidak dapat diedit karena project sudah ditutup.'
        );
    }
}
