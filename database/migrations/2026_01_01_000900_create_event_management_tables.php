<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('event_type', ['camp', 'conference', 'mission_trip', 'training', 'worship', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['draft', 'planned', 'open_registration', 'ongoing', 'completed', 'cancelled'])->default('planned');
            $table->integer('capacity')->nullable();
            $table->decimal('registration_fee', 14, 2)->default(0);
            $table->boolean('featured')->default(false);
            $table->string('cover_emoji')->nullable();
            $table->string('organizer')->nullable();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('start_date');
            $table->index('status');
        });

        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('speaker')->nullable();
            $table->string('facilitator')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'attended', 'no_show', 'cancelled'])->default('pending');
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->date('registered_on')->nullable();
            $table->string('checked_in_by')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('pledge_no')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'fulfilled', 'cancelled'])->default('pending');
            $table->enum('frequency', ['one_time', 'monthly', 'weekly'])->default('one_time');
            $table->text('notes')->nullable();
            $table->date('pledge_date');
            $table->date('due_date')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('pledge_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pledge_id')->constrained('pledges')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->enum('method', ['cash', 'bank', 'mobile'])->default('cash');
            $table->string('reference')->nullable();
            $table->date('pay_date');
            $table->foreignId('receipt_payment_id')->nullable()->constrained('receipt_payments')->nullOnDelete();
            $table->string('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledge_payments');
        Schema::dropIfExists('pledges');
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('event_sessions');
        Schema::dropIfExists('events');
    }
};
