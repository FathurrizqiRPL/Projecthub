<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(
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
    public function update(
        Request $request,
        Task $task,
        Subtask $subtask
    ): RedirectResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        $this->ensureTaskCanBeEdited($task);

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'in:pending,completed'],
            'completion_note' => ['sometimes', 'nullable', 'string'],
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

        return redirect()
            ->route('employee.tasks.show', ['task' => $task])
            ->with('success', 'Subtask berhasil diperbarui.');
    }

    private function authorizeTask(Task $task): void
    {
        abort_unless(
            $task->assignees()->where('users.id', Auth::id())->exists(),
            403
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
            in_array($task->status, ['pending', 'in_progress', 'revision'], true),
            422,
            'Subtask tidak dapat diubah saat task sedang direview atau sudah selesai.'
        );
    }
}
