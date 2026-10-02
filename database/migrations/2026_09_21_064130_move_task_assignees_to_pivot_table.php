<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasks')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->each(function ($task) {
                DB::table('task_assignees')->insertOrIgnore([
                    'task_id' => $task->id,
                    'user_id' => $task->assigned_to,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['assigned_to', 'status']);
            $table->dropColumn('assigned_to');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('project_stage_id')
                ->constrained('users')
                ->restrictOnDelete();
        });

        $assignees = DB::table('task_assignees')
            ->orderBy('id')
            ->get()
            ->groupBy('task_id');

        foreach ($assignees as $taskId => $taskAssignees) {
            DB::table('tasks')
                ->where('id', $taskId)
                ->update([
                    'assigned_to' => $taskAssignees->first()->user_id,
                ]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['assigned_to', 'status']);
            $table->dropIndex(['status']);
        });
    }
};
