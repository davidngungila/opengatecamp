<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fellowships', function (Blueprint $table) {
            $table->string('diocese', 100)->nullable()->after('university');
        });

        // Migrate existing settings fellowships list to include diocese if stored as plain names
        // No data migration needed; diocese will be null initially and editable via UI.
    }

    public function down(): void
    {
        Schema::table('fellowships', function (Blueprint $table) {
            $table->dropColumn('diocese');
        });
    }
};