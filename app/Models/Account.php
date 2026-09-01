<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'is_active', 'is_cash'];

    protected $casts = ['is_cash' => 'boolean', 'is_active' => 'boolean'];

    public function journalLines() { return $this->hasMany(JournalLine::class); }

    public static function types(): array
    {
        return ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }
}
