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
        Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['admin', 'project_manager', 'employee'])
            ->default('employee');

        $table->enum('status', ['active', 'inactive'])
            ->default('active');

        $table->string('profile_photo')->nullable();
        $table->string('department')->nullable();
        $table->text('skills')->nullable();
        $table->string('phone')->nullable();
        $table->date('joined_at')->nullable();

        $table->boolean('must_change_password')
            ->default(true);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'role',
            'status',
            'profile_photo',
            'department',
            'skills',
            'phone',
            'joined_at',
            'must_change_password',
        ]);
    });
    }
};
