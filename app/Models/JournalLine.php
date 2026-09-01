<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['journal_entry_id', 'account_id', 'description', 'debit', 'credit'];

    protected $casts = ['debit' => 'float', 'credit' => 'float'];

    public function entry() { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(Account::class); }
}
