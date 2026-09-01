<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'title', 'slug', 'event_type', 'description', 'venue', 'location',
        'start_date', 'end_date', 'start_time', 'end_time', 'status',
        'capacity', 'registration_fee', 'featured', 'cover_emoji', 'organizer',
        'budget_id', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'featured' => 'boolean',
        'registration_fee' => 'float',
        'capacity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title).'-'.Str::random(4);
            }
        });
    }

    public function sessions() { return $this->hasMany(EventSession::class)->orderBy('sort_order'); }
    public function attendees() { return $this->hasMany(EventAttendee::class); }
    public function pledges() { return $this->hasMany(Pledge::class); }
    public function budget() { return $this->belongsTo(Budget::class); }

    public static function types(): array
    {
        return [
            'camp'         => 'Camp',
            'conference'   => 'Conference',
            'mission_trip' => 'Mission Trip',
            'training'     => 'Training',
            'worship'      => 'Worship',
            'other'        => 'Other',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft'            => 'Draft',
            'planned'          => 'Planned',
            'open_registration'=> 'Open Registration',
            'ongoing'          => 'Ongoing',
            'completed'        => 'Completed',
            'cancelled'        => 'Cancelled',
        ];
    }

    public function getTypeLabel(): string
    {
        return static::types()[$this->event_type] ?? $this->event_type;
    }

    public function getStatusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft'            => 'neutral',
            'planned'          => 'info',
            'open_registration'=> 'success',
            'ongoing'          => 'warning',
            'completed'        => 'purple',
            'cancelled'        => 'danger',
            default            => 'neutral',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->event_type) {
            'camp'         => 'purple',
            'conference'   => 'blue',
            'mission_trip' => 'success',
            'training'     => 'info',
            'worship'      => 'warning',
            default        => 'neutral',
        };
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['planned', 'open_registration'], true)
            && $this->start_date->isFuture();
    }

    public function isOngoingOrUpcoming(): bool
    {
        return in_array($this->status, ['planned', 'open_registration', 'ongoing'], true);
    }

    public function getAttendeeCountAttribute(): int
    {
        return $this->attendees()->whereIn('status', ['confirmed', 'attended'])->count();
    }

    public function getRegisteredCountAttribute(): int
    {
        return $this->attendees()->count();
    }

    public function getPledgedTotalAttribute(): float
    {
        return (float) $this->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('amount');
    }

    public function getPledgedPaidAttribute(): float
    {
        return (float) $this->pledges()->whereIn('status', ['pending', 'partial', 'fulfilled'])->sum('paid_amount');
    }

    public function getBudgetTotalAttribute(): ?float
    {
        return $this->budget_id ? $this->budget?->amount : null;
    }
}
