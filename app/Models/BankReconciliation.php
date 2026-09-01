<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    protected $fillable = [
        'account_id', 'statement_date', 'statement_balance',
        'ledger_balance', 'difference', 'notes', 'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'statement_balance' => 'float',
        'ledger_balance' => 'float',
        'difference' => 'float',
    ];

    public function account() { return $this->belongsTo(Account::class); }
}
