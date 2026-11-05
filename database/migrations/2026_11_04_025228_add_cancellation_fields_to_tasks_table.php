<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->enum('status', [
                'pending',
                'in_progress',
                'waiting_review',
                'revision',
                'completed',
                'cancelled',
            ])->default('pending')->change();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tasks')
            ->where('status', 'cancelled')
            ->update(['status' => 'pending']);

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn('cancelled_at');
            $table->enum('status', [
                'pending',
                'in_progress',
                'waiting_review',
                'revision',
                'completed',
            ])->default('pending')->change();
        });
    }
};
