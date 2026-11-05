<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RevisionItem;
use App\Models\Subtask;
use App\Models\SubtaskAttachment;
use App\Models\Task;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class KaryawanController extends Controller
{
    public function projectsIndex(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        $assignedTasks = fn ($query) => $query->whereHas(
            'assignees',
            fn ($assigneeQuery) => $assigneeQuery
                ->where('users.id', Auth::id())
        );

        $projects = Project::query()
            ->whereHas('tasks', $assignedTasks)
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($projectQuery) => $projectQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')))
            ->with(['currentStage'])
            ->withCount([
                'tasks',
                'tasks as my_tasks_count' => $assignedTasks,
            ])
            ->orderBy('name')
            ->get();

        return view('employee.projects.index', compact('projects', 'search'));
    }

    public function projectsShow(Project $project): View
    {
        $this->authorizeProjectAccess($project);

        $project->load(['stages', 'currentStage']);
        $validated = request()->validate([
            'stage' => ['nullable', 'integer'],
        ]);
        $selectedStageId = $validated['stage'] ?? $project->current_stage_id;

        if ($selectedStageId !== null) {
            $stage = $project->stages->firstWhere('id', (int) $selectedStageId);
            abort_unless($stage, 404);
        } else {
            $stage = $project->stages->first();
        }

        $tasks = $stage
            ? $stage->tasks()
                ->with([
                    'assignees',
                    'completionException',
                    'subtasks',
                ])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
            : collect();

        $myTasks = $tasks
            ->filter(fn (Task $task): bool => $task->assignees->contains('id', Auth::id()))
            ->values();
        $teammateTasks = $tasks
            ->reject(fn (Task $task): bool => $task->assignees->contains('id', Auth::id()))
            ->values();

        return view(
            'employee.projects.show',
            compact('project', 'stage', 'tasks', 'myTasks', 'teammateTasks')
        );
    }

    public function tasksIndex(): View
    {
        $tasks = Task::query()
            ->whereHas(
                'assignees',
                fn ($query) => $query
                    ->where('users.id', Auth::id())
            )
            ->with([
                'project',
                'stage',
                'completionException',
                'assignees',
                'subtasks',
            ])
            ->latest()
            ->get();

        $openTasks = $tasks->filter(fn (Task $task): bool => ! in_array($task->project?->status, ['completed', 'cancelled'], true)
                && ! $task->completionException
                && $task->status !== 'cancelled'
        );

        $taskSummary = [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'revision' => $tasks->where('status', 'revision')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
        ];

        $newTasks = $openTasks
            ->where('status', 'pending')
            ->values();

        $quickActionTasks = [
            'revision' => $openTasks->first(fn (Task $task): bool => $task->status === 'revision'),
        ];

        return view(
            'employee.tasks.index',
            compact('tasks', 'newTasks', 'taskSummary', 'quickActionTasks')
        );
    }

    public function tasksShow(Task $task): View
    {
        $isAssignee = $this->authorizeTaskView($task);

        $task->load([
            'project',
            'stage',
            'completionException',
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
                    'attachments',
                ])
                ->orderBy('cycle')
                ->orderBy('id'),
        ]);

        $totalSubtasks = $task->subtasks->count();

        $completedSubtasks = $task->subtasks
            ->where('status', 'completed')
            ->count();
        $canEditTask = $isAssignee
            && ! in_array($task->project->status, ['completed', 'cancelled'], true);

        return view(
            'employee.tasks.show',
            compact(
                'task',
                'totalSubtasks',
                'completedSubtasks',
                'isAssignee',
                'canEditTask'
            )
        );
    }

    public function startTask(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);
        $this->ensureProjectIsOpen($task);

        abort_unless(
            $task->status === 'pending',
            422,
            'Task ini tidak dapat dimulai dari status saat ini.'
        );

        $task->update(['status' => 'in_progress']);

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Task berhasil dimulai.');
    }

    public function submitTaskForReview(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

        DB::transaction(function () use ($task): void {
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            $this->authorizeTask($task);
            $this->ensureProjectIsOpen($task);

            abort_unless(
                $task->status === 'in_progress',
                422,
                'Task hanya dapat diajukan saat sedang dikerjakan.'
            );

            $totalSubtasks = $task->subtasks()->count();
            $completedSubtasks = $task->subtasks()
                ->where('status', 'completed')
                ->count();

            abort_unless(
                $totalSubtasks > 0 && $completedSubtasks === $totalSubtasks,
                422,
                'Buat minimal satu subtask dan selesaikan semua subtask sebelum mengajukan review.'
            );

            $task->update([
                'status' => 'waiting_review',
                'review_cycle' => $task->review_cycle + 1,
            ]);

        });

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Task berhasil diajukan untuk review.');
    }

    public function updateRevisionItem(
        Request $request,
        Task $task,
        RevisionItem $revisionItem
    ): RedirectResponse {
        $this->authorizeTask($task);
        $this->ensureProjectIsOpen($task);

        abort_unless(
            $task->status === 'revision'
            && $revisionItem->task_id === $task->id
            && $revisionItem->cycle === $task->review_cycle,
            404
        );

        abort_unless(
            $revisionItem->status !== 'resolved',
            422,
            'Item revisi ini sudah diselesaikan.'
        );

        $validated = $request->validate([
            'completion_note' => ['required', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip'],
        ]);

        DB::transaction(function () use ($task, $revisionItem, $validated, $request): void {
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);
            $revisionItem = RevisionItem::query()
                ->lockForUpdate()
                ->findOrFail($revisionItem->id);

            $this->authorizeTask($task);
            $this->ensureProjectIsOpen($task);

            abort_unless(
                $task->status === 'revision'
                    && $revisionItem->task_id === $task->id
                    && $revisionItem->cycle === $task->review_cycle
                    && $revisionItem->status !== 'resolved',
                422,
                'Item revisi ini tidak dapat diperbarui.'
            );

            $revisionItem->update([
                'status' => 'resolved',
                'completion_note' => $validated['completion_note'],
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store('revision-attachments', 'local');
                $revisionItem->attachments()->create([
                    'uploaded_by' => Auth::id(),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            $hasOpenItems = $task->revisionItems()
                ->where('cycle', $task->review_cycle)
                ->where('status', '!=', 'resolved')
                ->exists();

            if (! $hasOpenItems) {
                $task->update(['status' => 'in_progress']);
            }
        });

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Laporan penyelesaian revisi berhasil dikirim.');
    }

    private function authorizeTask(Task $task): void
    {
        abort_unless(
            $task->assignees()
                ->where(
                    'users.id',
                    Auth::id()
                )
                ->exists(),
            403
        );
    }

    private function authorizeTaskView(Task $task): bool
    {
        $isAssignee = $task->assignees()
            ->where('users.id', Auth::id())
            ->exists();

        if (! $isAssignee) {
            $this->authorizeProjectAccess($task->project);
        }

        return $isAssignee;
    }

    private function authorizeProjectAccess(Project $project): void
    {
        abort_unless(
            $project->tasks()
                ->whereHas(
                    'assignees',
                    fn ($query) => $query
                        ->where('users.id', Auth::id())
                )
                ->exists(),
            403
        );
    }

    private function ensureProjectIsOpen(Task $task): void
    {
        abort_unless(
            ! in_array($task->project()->value('status'), ['completed', 'cancelled'], true),
            422,
            'Task tidak dapat diedit karena project sudah ditutup.'
        );
    }

    public function uploadSubtaskAttachment(
        Request $request,
        Task $task,
        Subtask $subtask
    ): RedirectResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        $this->ensureTaskCanBeEdited($task);

        $validated = $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('subtask-attachments', 'local');

        $subtask->attachments()->create([
            'uploaded_by' => Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Lampiran berhasil diunggah.');
    }

    public function downloadSubtaskAttachment(
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): StreamedResponse {
        $this->authorizeTaskView($task);
        abort_unless($subtask->task_id === $task->id, 403);
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

    public function deleteSubtaskAttachment(
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): RedirectResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        $this->ensureTaskCanBeEdited($task);
        abort_unless($attachment->subtask_id === $subtask->id, 404);
        abort_unless($attachment->uploaded_by === Auth::id(), 403);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Lampiran berhasil dihapus.');
    }

    public function replaceSubtaskAttachment(
        Request $request,
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): RedirectResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        $this->ensureTaskCanBeEdited($task);
        abort_unless($attachment->subtask_id === $subtask->id, 404);
        abort_unless($attachment->uploaded_by === Auth::id(), 403);

        $validated = $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ]);

        $file = $validated['attachment'];
        $newFilePath = $file->store('subtask-attachments', 'local');

        if ($newFilePath === false) {
            throw new RuntimeException('Lampiran pengganti gagal disimpan.');
        }

        try {
            $oldFilePath = DB::transaction(function () use (
                $task,
                $subtask,
                $attachment,
                $file,
                $newFilePath
            ): string {
                $lockedTask = Task::query()
                    ->lockForUpdate()
                    ->findOrFail($task->id);
                $lockedAttachment = SubtaskAttachment::query()
                    ->lockForUpdate()
                    ->findOrFail($attachment->id);

                $this->authorizeTaskAndSubtask($lockedTask, $subtask);
                $this->ensureTaskCanBeEdited($lockedTask);
                abort_unless($lockedAttachment->subtask_id === $subtask->id, 404);
                abort_unless($lockedAttachment->uploaded_by === Auth::id(), 403);

                $previousFilePath = $lockedAttachment->file_path;
                $lockedAttachment->update([
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $newFilePath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                return $previousFilePath;
            });
        } catch (Throwable $exception) {
            if (
                Storage::disk('local')->exists($newFilePath)
                && ! Storage::disk('local')->delete($newFilePath)
            ) {
                throw new RuntimeException(
                    'Lampiran gagal diperbarui dan file pengganti sementara tidak dapat dibersihkan.',
                    previous: $exception
                );
            }

            throw $exception;
        }

        if (
            $oldFilePath !== $newFilePath
            && Storage::disk('local')->exists($oldFilePath)
            && ! Storage::disk('local')->delete($oldFilePath)
        ) {
            throw new RuntimeException(
                'Lampiran baru tersimpan, tetapi file lampiran lama gagal dihapus.'
            );
        }

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Lampiran berhasil diganti.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeSubtask(
        Request $request,
        Task $task
    ): RedirectResponse {
        $this->authorizeTask($task);
        $this->ensureTaskCanBeEdited($task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $task->subtasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Subtask berhasil dibuat.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateSubtask(
        Request $request,
        Task $task,
        Subtask $subtask
    ): RedirectResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        $this->ensureTaskCanBeEdited($task);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:pending,completed'],
            'completion_note' => [
                'required_if:status,completed',
                'nullable',
                'string',
                'max:5000',
            ],
            'attachments' => [
                'required_if:status,completed',
                'array',
                'min:1',
                'max:5',
            ],
            'attachments.*' => [
                'required_with:attachments',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ]);

        $hasUpdates = array_key_exists('title', $validated)
            || array_key_exists('description', $validated)
            || array_key_exists('status', $validated)
            || array_key_exists('completion_note', $validated)
            || ! empty($validated['attachments']);

        abort_unless($hasUpdates, 422, 'Tidak ada perubahan untuk disimpan.');

        $subtaskUpdates = array_intersect_key(
            $validated,
            array_flip(['title', 'description'])
        );

        if (array_key_exists('status', $validated)) {
            $isCompleted = $validated['status'] === 'completed';

            $statusUpdates = [
                'status' => $validated['status'],
                'completed_by' => null,
                'completed_at' => null,
                'completion_note' => null,
            ];

            if ($isCompleted) {
                $statusUpdates['completed_by'] = Auth::id();
                $statusUpdates['completed_at'] = now();
                $statusUpdates['completion_note'] = $validated['completion_note'] ?? null;
            }

            $subtaskUpdates = array_merge($subtaskUpdates, $statusUpdates);
        } elseif (array_key_exists('completion_note', $validated)) {
            $subtaskUpdates['completion_note'] = $validated['completion_note'];
        }

        if ($subtaskUpdates !== []) {
            $subtask->update($subtaskUpdates);
        }

        foreach ($validated['attachments'] ?? [] as $file) {
            $path = $file->store('subtask-attachments', 'local');

            $subtask->attachments()->create([
                'uploaded_by' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $successMessage = 'Subtask berhasil diperbarui.';

        if (($validated['status'] ?? null) === 'completed') {
            $successMessage = 'Laporan penyelesaian subtask berhasil dikirim.';
        }

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', $successMessage);
    }

    private function authorizeTaskAndSubtask(
        Task $task,
        Subtask $subtask
    ): void {
        $this->authorizeTask($task);
        abort_unless($subtask->task_id === $task->id, 403);
    }

    private function ensureTaskCanBeEdited(Task $task): void
    {
        abort_unless(
            ! in_array($task->project()->value('status'), ['completed', 'cancelled'], true),
            422,
            'Task tidak dapat diedit karena project sudah ditutup.'
        );

        abort_unless(
            in_array($task->status, ['pending', 'in_progress', 'revision'], true),
            422,
            'Subtask tidak dapat diedit saat task sedang direview atau sudah selesai.'
        );
    }
}
