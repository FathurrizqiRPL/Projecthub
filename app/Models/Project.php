<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'start_date',
        'deadline',
        'status',
        'current_stage_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed'
            && $this->deadline !== null
            && $this->deadline->isBefore(today());
    }

    protected function progress(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $tasks = $this->tasks()
                    ->where('status', '!=', 'cancelled')
                    ->withCount([
                        'subtasks',
                        'subtasks as completed_subtasks_count' => fn ($query) => $query
                            ->where('status', 'completed'),
                    ])
                    ->get(['id', 'status']);

                if ($tasks->isEmpty()) {
                    return 0;
                }

                $taskProgressTotal = $tasks->sum(function (Task $task): float {
                    if ($task->status === 'completed') {
                        return 100;
                    }

                    if ($task->subtasks_count === 0) {
                        return 0;
                    }

                    return ($task->completed_subtasks_count / $task->subtasks_count) * 100;
                });

                return (int) round($taskProgressTotal / $tasks->count());
            }
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStage::class)
            ->orderBy('position');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(
            ProjectStage::class,
            'current_stage_id'
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'project_members'
        )->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function completionExceptions(): HasMany
    {
        return $this->hasMany(ProjectCompletionException::class);
    }
}
