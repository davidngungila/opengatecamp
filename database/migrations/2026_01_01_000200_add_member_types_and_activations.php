<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->enum('member_type', ['student', 'non_student'])->default('non_student')->after('status');
            $table->enum('staff_type', ['staff', 'non_staff'])->nullable()->after('member_type');
        });

        Schema::create('member_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->timestamp('activated_at');
            $table->string('activated_by')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'financial_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_activations');
        Schema::table('members', fn (Blueprint $t) => $t->dropColumn(['member_type', 'staff_type']));
    }
};
