<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->string('short_code', 12)->nullable()->unique()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->dropColumn('short_code');
        });
    }
};