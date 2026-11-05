<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'project_stage_id',
        'created_by',
        'title',
        'description',
        'deadline',
        'status',
        'review_cycle',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'review_cycle' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(
            ProjectStage::class,
            'project_stage_id'
        );
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'task_assignees'
        )->withTimestamps();
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    public function revisionItems(): HasMany
    {
        return $this->hasMany(RevisionItem::class);
    }

    public function completionException(): HasOne
    {
        return $this->hasOne(ProjectCompletionException::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    protected function progress(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $total = $this->subtasks()->count();

                if ($total === 0) {
                    return 0;
                }

                $completed = $this->subtasks()
                    ->where('status', 'completed')
                    ->count();

                return (int) round(
                    ($completed / $total) * 100
                );
            }
        );
    }
}
