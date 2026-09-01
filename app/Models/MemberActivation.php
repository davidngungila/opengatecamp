<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberActivation extends Model
{
    protected $fillable = ['member_id', 'financial_year_id', 'activated_at', 'activated_by'];

    protected $casts = ['activated_at' => 'datetime'];

    public function member() { return $this->belongsTo(Member::class); }
    public function financialYear() { return $this->belongsTo(FinancialYear::class); }
}
