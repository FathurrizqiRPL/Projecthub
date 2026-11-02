<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_completion_exceptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('task_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('waived_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('stage_name');
            $table->string('task_title');
            $table->string('task_status');
            $table->text('reason');
            $table->timestamps();

            $table->unique(['project_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_completion_exceptions');
    }
};
