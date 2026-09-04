<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalCardContribution extends Model
{
    protected $fillable = [
        'digital_card_id', 'contributor_name', 'contributor_phone', 'contributor_email',
        'amount', 'method', 'reference_no', 'note', 'status', 'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function digitalCard()
    {
        return $this->belongsTo(DigitalCard::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function statuses(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'failed' => 'Failed',
        ];
    }

    public static function methods(): array
    {
        return [
            'cash' => 'Cash',
            'bank' => 'Bank Transfer',
            'mobile' => 'Mobile Money',
        ];
    }

    public function getMethodLabel(): string
    {
        return static::methods()[$this->method] ?? $this->method;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed' => 'success',
            'failed' => 'danger',
            default => 'neutral',
        };
    }
}
