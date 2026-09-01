<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptPayment extends Model
{
    protected $fillable = [
        'type', 'doc_no', 'pay_date', 'party', 'category_account_id',
        'money_account_id', 'amount', 'method', 'reference',
        'description', 'journal_entry_id', 'created_by',
    ];

    protected $casts = ['pay_date' => 'date', 'amount' => 'float'];

    public function categoryAccount() { return $this->belongsTo(Account::class, 'category_account_id'); }
    public function moneyAccount() { return $this->belongsTo(Account::class, 'money_account_id'); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}
