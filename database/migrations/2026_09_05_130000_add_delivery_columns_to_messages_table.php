<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('delivery_status', 20)->nullable()->after('status');
            $table->timestamp('delivery_checked_at')->nullable()->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'delivery_checked_at']);
        });
    }
};