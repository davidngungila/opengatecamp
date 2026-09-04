<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_card_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_card_id')->constrained('digital_cards')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone');
            $table->string('token', 40)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_card_recipients');
    }
};
