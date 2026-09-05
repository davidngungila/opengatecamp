<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('phone');
            $table->string('message_id', 100)->nullable()->after('token');
            $table->string('delivery_status', 20)->nullable()->after('message_id');
            $table->timestamp('delivery_checked_at')->nullable()->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->dropColumn(['status', 'message_id', 'delivery_status', 'delivery_checked_at']);
        });
    }
};