<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    protected $fillable = [
        'event_id', 'member_id', 'name', 'phone', 'email', 'status',
        'amount_paid', 'fee_amount', 'payment_method', 'pickup_location', 'notes', 'registered_on',
        'checked_in_by', 'checked_in_at', 'journal_entry_id',
    ];

    protected $casts = [
        'registered_on' => 'date',
        'checked_in_at' => 'datetime',
        'amount_paid' => 'float',
        'fee_amount' => 'float',
    ];

    public function event() { return $this->belongsTo(Event::class); }
    public function member() { return $this->belongsTo(Member::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }

    public static function statuses(): array
    {
        return [
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'attended'  => 'Attended',
            'no_show'   => 'No Show',
            'cancelled' => 'Cancelled',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'confirmed' => 'info',
            'attended'  => 'success',
            'no_show'   => 'danger',
            'cancelled' => 'neutral',
            default     => 'neutral',
        };
    }
}
