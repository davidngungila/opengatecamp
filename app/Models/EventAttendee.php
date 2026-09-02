<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendee extends Model
{
    protected $fillable = [
        'event_id', 'member_id', 'name', 'phone', 'email', 'fellowship', 'status',
        'amount_paid', 'fee_amount', 'payment_method', 'pickup_location', 'notes', 'registered_on',
        'checked_in_by', 'checked_in_at', 'ticket_no', 'ticket_sent_at', 'journal_entry_id',
    ];

    protected $casts = [
        'registered_on' => 'date',
        'checked_in_at' => 'datetime',
        'ticket_sent_at' => 'datetime',
        'amount_paid' => 'float',
        'fee_amount' => 'float',
    ];

    public function event() { return $this->belongsTo(Event::class); }
    public function member() { return $this->belongsTo(Member::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }

    public function hasCompletedContribution(): bool
    {
        return (float) $this->amount_paid >= (float) $this->fee_amount && (float) $this->fee_amount > 0;
    }

    public function getTicketNo(): string
    {
        if ($this->ticket_no) {
            return $this->ticket_no;
        }

        // Lazily issue a short 6-character alphanumeric code (e.g. SD43D7) and persist it.
        $code = $this->issueTicketCode();
        $this->update(['ticket_no' => $code]);
        $this->ticket_no = $code;

        return $code;
    }

    private function issueTicketCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (self::where('ticket_no', $code)->exists());

        return $code;
    }

    public function getRegionLabel(): string
    {
        return match ($this->pickup_location) {
            'arusha' => 'Arusha',
            'moshi'  => 'Moshi',
            default  => '—',
        };
    }

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
