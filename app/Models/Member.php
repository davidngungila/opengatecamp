<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'member_no', 'name', 'gender', 'date_of_birth', 'marital_status',
        'phone', 'email', 'address', 'family_id', 'group_id', 'ministry_id',
        'emergency_name', 'emergency_relationship', 'emergency_phone',
        'status', 'joined_on', 'member_type', 'staff_type',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joined_on' => 'date',
    ];

    public function family() { return $this->belongsTo(Family::class); }
    public function group() { return $this->belongsTo(Group::class); }
    public function ministry() { return $this->belongsTo(Ministry::class); }
    public function activations() { return $this->hasMany(MemberActivation::class); }
    public function eventAttendees() { return $this->hasMany(EventAttendee::class); }
    public function pledges() { return $this->hasMany(Pledge::class); }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function isStudent(): bool
    {
        return $this->member_type === 'student';
    }

    public function isActivatedFor(?int $financialYearId): bool
    {
        if (! $this->isStudent() || ! $financialYearId) {
            return true;
        }

        return $this->activations()->where('financial_year_id', $financialYearId)->exists();
    }
}
