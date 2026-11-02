<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCompletionException extends Model
{
    protected $fillable = [
        'project_id',
        'task_id',
        'waived_by',
        'stage_name',
        'task_title',
        'task_status',
        'reason',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function waivingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
