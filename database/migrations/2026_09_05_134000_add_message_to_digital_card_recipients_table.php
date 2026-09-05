<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->text('message')->nullable()->after('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->dropColumn('message');
        });
    }
};