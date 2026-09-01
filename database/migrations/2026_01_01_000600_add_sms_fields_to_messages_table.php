<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('recipients');
            $table->string('api_message_id', 100)->nullable()->after('status');
            $table->text('api_response')->nullable()->after('api_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['phone', 'api_message_id', 'api_response']);
        });
    }
};
