<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_cash')->default(false)->after('type');
        });

        Schema::create('receipt_payments', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['receipt', 'payment']);
            $table->string('doc_no')->unique();
            $table->date('pay_date');
            $table->string('party');
            $table->foreignId('category_account_id')->constrained('accounts');
            $table->foreignId('money_account_id')->constrained('accounts');
            $table->decimal('amount', 14, 2);
            $table->enum('method', ['cash', 'bank', 'mobile'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fy_id')->constrained('financial_years')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['fy_id', 'account_id']);
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 14, 2);
            $table->decimal('ledger_balance', 14, 2);
            $table->decimal('difference', 14, 2);
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('receipt_payments');
        Schema::table('accounts', fn (Blueprint $t) => $t->dropColumn('is_cash'));
    }
};
