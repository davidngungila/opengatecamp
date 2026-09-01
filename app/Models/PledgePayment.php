<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PledgePayment extends Model
{
    protected $fillable = [
        'pledge_id', 'amount', 'method', 'reference', 'pay_date',
        'receipt_payment_id', 'journal_entry_id', 'recorded_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'pay_date' => 'date',
    ];

    public function pledge() { return $this->belongsTo(Pledge::class); }
    public function receiptPayment() { return $this->belongsTo(ReceiptPayment::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}