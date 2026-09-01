<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pledge_payments', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('receipt_payment_id')
                ->constrained('journal_entries')->nullOnDelete();
        });

        Schema::table('event_attendees', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->after('checked_in_by')
                ->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pledge_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });

        Schema::table('event_attendees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });
    }
};