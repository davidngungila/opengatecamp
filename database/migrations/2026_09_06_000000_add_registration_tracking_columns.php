<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->string('registered_by')->nullable()->after('registered_on');
        });

        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->string('added_by')->nullable();
        });

        Schema::table('digital_card_contributions', function (Blueprint $table) {
            $table->string('recorded_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
            $table->dropColumn('registered_by');
        });

        Schema::table('digital_card_recipients', function (Blueprint $table) {
            $table->dropColumn('added_by');
        });

        Schema::table('digital_card_contributions', function (Blueprint $table) {
            $table->dropColumn('recorded_by');
        });
    }
};