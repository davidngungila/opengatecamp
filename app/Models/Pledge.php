<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pledge extends Model
{
    protected $fillable = [
        'event_id', 'member_id', 'pledge_no', 'name', 'email', 'phone',
        'amount', 'paid_amount', 'status', 'frequency', 'notes',
        'pledge_date', 'due_date', 'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'pledge_date' => 'date',
        'due_date' => 'date',
    ];

    public function event() { return $this->belongsTo(Event::class); }
    public function member() { return $this->belongsTo(Member::class); }
    public function payments() { return $this->hasMany(PledgePayment::class); }

    public static function statuses(): array
    {
        return ['pending' => 'Pending', 'partial' => 'Partial', 'fulfilled' => 'Fulfilled', 'cancelled' => 'Cancelled'];
    }

    public static function frequencies(): array
    {
        return ['one_time' => 'One-time', 'monthly' => 'Monthly', 'weekly' => 'Weekly'];
    }

    public static function nextPledgeNo(): string
    {
        $max = (int) substr((string) (static::query()->max('pledge_no') ?? 'PLG-0000'), -4);
        return 'PLG-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'partial'   => 'info',
            'fulfilled' => 'success',
            'cancelled' => 'neutral',
            default     => 'neutral',
        };
    }

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
