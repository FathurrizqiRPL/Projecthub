<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\RevisionItem;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(): View
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

    public function show(Task $task): View
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

    public function start(Task $task): RedirectResponse
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

    public function submitForReview(Task $task): RedirectResponse
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
}
