<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['activity_task_id', 'user_id']);
            $table->index('user_id');
        });

        // Backfill: copy the legacy single assignee into the pivot.
        DB::table('activity_tasks')
            ->whereNotNull('assignee_id')
            ->orderBy('id')
            ->each(function (object $task) {
                $name = DB::table('users')->where('id', $task->assignee_id)->value('name');
                DB::table('activity_task_assignees')->insert([
                    'activity_task_id' => $task->id,
                    'user_id'          => $task->assignee_id,
                    'user_name'        => $name,
                    'assigned_by_id'   => $task->assigned_by_id,
                    'created_at'       => $task->created_at,
                    'updated_at'       => $task->created_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_task_assignees');
    }
};