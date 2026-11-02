<?php

namespace App\Http\Controllers\ProjectManager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\SubtaskAttachment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectTaskController extends Controller
{
    public function allTasks(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'in:all,review'],
        ]);

        $search = trim($validated['search'] ?? '');
        $reviewOnly = ($validated['filter'] ?? 'all') === 'review';
        $taskFilter = fn ($query) => $reviewOnly
            ? $query->where('status', 'waiting_review')
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
                    ])
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->limit(2),
            ])
            ->withCount([
                'tasks as waiting_review_tasks_count' => fn ($query) => $query
                    ->where('status', 'waiting_review'),
            ])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $reviewTaskCount = Project::query()
            ->where('created_by', Auth::id())
            ->whereHas('tasks', fn ($query) => $query
                ->where('status', 'waiting_review'))
            ->withCount([
                'tasks as waiting_review_tasks_count' => fn ($query) => $query
                    ->where('status', 'waiting_review'),
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

    public function index(Project $project): View
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
                ])
                ->latest()
                ->get()
            : collect();

        return view(
            'project-manager.tasks.index',
            compact('project', 'stage', 'tasks')
        );
    }

    public function create(Project $project): View
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

    public function store(
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

        DB::transaction(function () use (
            $project,
            $validated
        ): void {
            $task = $project->tasks()->create([
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
                'Task berhasil dibuat.'
            );
    }

    public function show(
        Project $project,
        Task $task
    ): View {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);

        $task->load([
            'stage',
            'creator',
            'assignees',
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
                ])
                ->orderBy('cycle')
                ->orderBy('id'),
        ]);

        $totalSubtasks = $task->subtasks->count();

        $completedSubtasks = $task->subtasks
            ->where('status', 'completed')
            ->count();

        return view(
            'project-manager.tasks.show',
            compact(
                'project',
                'task',
                'totalSubtasks',
                'completedSubtasks'
            )
        );
    }

    public function downloadAttachment(
        Project $project,
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): StreamedResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);
        abort_unless($subtask->task_id === $task->id, 404);
        abort_unless($attachment->subtask_id === $subtask->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    public function approve(
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);

        DB::transaction(function () use ($project, $task): void {
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeProject($project);
            $this->authorizeTask($project, $task);

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
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeProject($project);
            $this->authorizeTask($project, $task);

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

    public function destroy(
        Project $project,
        Task $task
    ): RedirectResponse {
        $this->authorizeProject($project);
        $this->authorizeTask($project, $task);

        abort_unless(
            $task->status === 'pending',
            422,
            'Task yang sudah dikerjakan tidak dapat dihapus.'
        );

        $stageId = $task->project_stage_id;

        $task->delete();

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
                'Task berhasil dihapus.'
            );
    }

    private function authorizeProject(
        Project $project
    ): void {
        abort_unless(
            (int) $project->created_by ===
            (int) Auth::id(),
            403
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
}
