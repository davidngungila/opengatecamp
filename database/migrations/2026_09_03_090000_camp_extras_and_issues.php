<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attendee registration fields: fellowship, origin region (reuses pickup_location),
        // ticket details. pickup_location already stores arusha/moshi (the origin region field).
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->string('fellowship')->nullable()->after('email');
            $table->string('ticket_no')->nullable()->unique()->after('notes');
            $table->timestamp('ticket_sent_at')->nullable()->after('checked_in_at');
        });

        // Issues / support tracking
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->string('assignee')->nullable();
            $table->string('reported_by')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->dropColumn(['fellowship', 'ticket_no', 'ticket_sent_at']);
        });

        Schema::dropIfExists('issues');
    }
};