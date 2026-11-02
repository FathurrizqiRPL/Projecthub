<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Subtask;
use App\Models\SubtaskAttachment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubtaskController extends Controller
{
    public function uploadAttachment(
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

    public function downloadAttachment(
        Task $task,
        Subtask $subtask,
        SubtaskAttachment $attachment
    ): StreamedResponse {
        $this->authorizeTaskAndSubtask($task, $subtask);
        abort_unless($attachment->subtask_id === $subtask->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    public function deleteAttachment(
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
