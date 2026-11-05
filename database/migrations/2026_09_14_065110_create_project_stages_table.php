<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->unsignedInteger('position');
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'project_id',
                'position',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('current_stage_id')
                ->nullable()
                ->after('status')
                ->constrained('project_stages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_stage_id');
        });

        Schema::dropIfExists('project_stages');
    }
};
