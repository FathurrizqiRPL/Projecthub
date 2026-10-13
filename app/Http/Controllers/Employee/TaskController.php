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
                'assignees',
                'subtasks',
            ])
            ->latest()
            ->get();

        return view(
            'employee.tasks.index',
            compact('tasks')
        );
    }

    public function show(Task $task): View
    {
        $this->authorizeTask($task);

        $task->load([
            'project',
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
            'employee.tasks.show',
            compact(
                'task',
                'totalSubtasks',
                'completedSubtasks'
            )
        );
    }

    public function start(Task $task): RedirectResponse
    {
        $this->authorizeTask($task);

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
            'status' => ['required', 'in:in_progress,resolved'],
        ]);

        DB::transaction(function () use ($task, $revisionItem, $validated): void {
            $task = Task::query()
                ->lockForUpdate()
                ->findOrFail($task->id);
            $revisionItem = RevisionItem::query()
                ->lockForUpdate()
                ->findOrFail($revisionItem->id);

            $this->authorizeTask($task);

            abort_unless(
                $task->status === 'revision'
                    && $revisionItem->task_id === $task->id
                    && $revisionItem->cycle === $task->review_cycle
                    && $revisionItem->status !== 'resolved',
                422,
                'Item revisi ini tidak dapat diperbarui.'
            );

            $revisionItem->update([
                'status' => $validated['status'],
                'resolved_by' => $validated['status'] === 'resolved'
                    ? Auth::id()
                    : null,
                'resolved_at' => $validated['status'] === 'resolved'
                    ? now()
                    : null,
            ]);

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
            ->with('success', 'Status item revisi berhasil diperbarui.');
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
}
