<?php

namespace App\Http\Controllers;

use App\Models\RevisionItem;
use App\Models\Subtask;
use App\Models\SubtaskAttachment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KaryawanController extends Controller
{
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

        $quickActionTasks = [
            'pending' => $openTasks->first(fn (Task $task): bool => $task->status === 'pending'),
            'revision' => $openTasks->first(fn (Task $task): bool => $task->status === 'revision'),
        ];

        return view(
            'employee.tasks.index',
            compact('tasks', 'taskSummary', 'quickActionTasks')
        );
    }

    public function tasksShow(Task $task): View
    {
        $this->authorizeTask($task);

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
        $canEditTask = ! in_array($task->project->status, ['completed', 'cancelled'], true);

        return view(
            'employee.tasks.show',
            compact(
                'task',
                'totalSubtasks',
                'completedSubtasks',
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
        $this->authorizeTaskAndSubtask($task, $subtask);
        abort_unless($attachment->subtask_id === $subtask->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->response(
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
            'status' => ['sometimes', 'required', 'in:pending,completed'],
            'completion_note' => [
                'required_if:status,completed',
                'nullable',
                'string',
                'max:5000',
            ],
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip',
            ],
        ]);

        if (array_key_exists('status', $validated)) {
            $isCompleted = $validated['status'] === 'completed';

            $subtask->update([
                'status' => $validated['status'],
                'completed_by' => $isCompleted ? Auth::id() : null,
                'completed_at' => $isCompleted ? now() : null,
                'completion_note' => $isCompleted
                    ? ($validated['completion_note'] ?? null)
                    : null,
            ]);
        } elseif (array_key_exists('completion_note', $validated)) {
            $subtask->update([
                'completion_note' => $validated['completion_note'],
            ]);
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

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with(
                'success',
                ($validated['status'] ?? null) === 'completed'
                    ? 'Laporan penyelesaian subtask berhasil dikirim.'
                    : 'Subtask berhasil diperbarui.'
            );
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
