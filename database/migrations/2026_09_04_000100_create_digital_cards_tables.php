<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_no', 20)->unique();
            $table->string('title');
            $table->text('message');
            $table->string('card_type', 50)->default('general');
            $table->string('background_color', 7)->default('#1a237e');
            $table->string('accent_color', 7)->default('#ffd700');
            $table->string('image_path', 500)->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->string('contributor_note', 500)->nullable();
            $table->string('cta_text', 100)->default('Contribute Now');
            $table->string('hash', 32)->unique();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_published')->default(false);
            $table->text('sms_text')->nullable();
            $table->unsignedInteger('contributions_count')->default(0);
            $table->decimal('total_contributions', 12, 2)->default(0);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index(['status', 'card_type']);
        });

        Schema::create('digital_card_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_card_id')->constrained()->cascadeOnDelete();
            $table->string('contributor_name', 255)->nullable();
            $table->string('contributor_phone', 20)->nullable();
            $table->string('contributor_email', 255)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20)->default('mobile');
            $table->string('reference_no', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['digital_card_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_card_contributions');
        Schema::dropIfExists('digital_cards');
    }
};
