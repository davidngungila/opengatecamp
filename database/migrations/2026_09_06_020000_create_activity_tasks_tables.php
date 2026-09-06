<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_no')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignee_name')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('challenges')->nullable();
            $table->text('way_forward')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index('deadline');
        });

        Schema::create('task_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('type');
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_updates');
        Schema::dropIfExists('activity_tasks');
    }
};