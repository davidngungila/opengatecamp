<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'is_active', 'is_cash'];

    protected $casts = ['is_cash' => 'boolean', 'is_active' => 'boolean'];

    public function journalLines() { return $this->hasMany(JournalLine::class); }

    public function ref(): string
    {
        return Crypt::encryptString('acct:'.$this->id);
    }

    public static function resolveRef(?string $ref): ?Account
    {
        if ($ref === null || $ref === '') {
            return null;
        }

        if (ctype_digit($ref)) {
            return static::find((int) $ref);
        }

        try {
            $decoded = Crypt::decryptString($ref);
        } catch (\Throwable $e) {
            return null;
        }

        if (! str_starts_with($decoded, 'acct:')) {
            return null;
        }

        return static::find((int) substr($decoded, 5));
    }

    public static function types(): array
    {
        return ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }
}
