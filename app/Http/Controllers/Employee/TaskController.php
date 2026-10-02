<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
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
