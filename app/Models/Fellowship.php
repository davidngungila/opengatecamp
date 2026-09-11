<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fellowship extends Model
{
    protected $fillable = [
        'name', 'type', 'university', 'diocese', 'contact_name', 'contact_phone', 'contact_email',
        'notes', 'capacity', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'fellowship_user')
            ->withPivot(['is_primary', 'title'])
            ->withTimestamps();
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function types(): array
    {
        return [
            'fellowship'     => 'Fellowship',
            'christian_union'=> 'Christian Union',
            'catholic_students' => 'Catholic Students Union',
            'other'          => 'Other',
        ];
    }

    public function getTypeLabel(): string
    {
        return $this->types()[$this->type] ?? ($this->type ?? '—');
    }

    public function primaryLeader(): ?User
    {
        return $this->leaders->first(fn ($user) => (bool) $user->pivot->is_primary) ?? $this->leaders->first();
    }

    public function getDelegationCountAttribute(): int
    {
        return (int) $this->attendees()->count();
    }

    public function getConfirmedCountAttribute(): int
    {
        return (int) $this->attendees()->whereIn('status', ['confirmed', 'attended'])->count();
    }

    public function getAttendedCountAttribute(): int
    {
        return (int) $this->attendees()->where('status', 'attended')->count();
    }

    public function getPaidSumAttribute(): float
    {
        return (float) $this->attendees()->sum('amount_paid');
    }
}