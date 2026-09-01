<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['fy_id', 'account_id', 'event_id', 'amount'];

    protected $casts = ['amount' => 'float'];

    public function fy() { return $this->belongsTo(FinancialYear::class, 'fy_id'); }
    public function account() { return $this->belongsTo(Account::class); }
    public function event() { return $this->belongsTo(Event::class); }
}