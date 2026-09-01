<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (! Schema::hasColumn('budgets', 'event_id')) {
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete()->after('account_id');
            }

            if (! collect(Schema::getIndexes('budgets'))->contains(fn ($i) => ($i['columns'] ?? []) === ['fy_id', 'account_id', 'event_id'])) {
                $table->unique(['fy_id', 'account_id', 'event_id']);

                if (collect(Schema::getIndexes('budgets'))->contains(fn ($i) => $i['name'] === 'budgets_fy_id_account_id_unique')) {
                    $table->dropUnique('budgets_fy_id_account_id_unique');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (collect(Schema::getIndexes('budgets'))->contains(fn ($i) => $i['name'] === 'budgets_fy_id_account_id_event_id_unique')) {
                $table->dropUnique('budgets_fy_id_account_id_event_id_unique');
            }
            if (! collect(Schema::getIndexes('budgets'))->contains(fn ($i) => $i['name'] === 'budgets_fy_id_account_id_unique')) {
                $table->unique(['fy_id', 'account_id']);
            }
            if (Schema::hasColumn('budgets', 'event_id')) {
                $table->dropConstrainedForeignId('event_id');
            }
        });
    }
};