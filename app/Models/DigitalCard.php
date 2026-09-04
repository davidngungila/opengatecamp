<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DigitalCard extends Model
{
    protected $fillable = [
        'card_no', 'title', 'message', 'card_type', 'background_color', 'accent_color',
        'image_path', 'event_id', 'target_amount', 'currency', 'contributor_note',
        'cta_text', 'hash', 'status', 'is_published', 'sms_text',
        'contributions_count', 'total_contributions', 'created_by',
    ];

    protected $casts = [
        'target_amount' => 'float',
        'total_contributions' => 'float',
        'contributions_count' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (DigitalCard $card) {
            if (empty($card->hash)) {
                $card->hash = Str::random(32);
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function contributions()
    {
        return $this->hasMany(DigitalCardContribution::class);
    }

    public static function types(): array
    {
        return [
            'camp_invitation' => 'Camp Invitation',
            'fundraising' => 'Fundraising',
            'thank_you' => 'Thank You',
            'birthday' => 'Birthday',
            'christmas' => 'Christmas',
            'general' => 'General',
        ];
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'closed' => 'Closed',
        ];
    }

    public static function nextCardNo(): string
    {
        $max = (int) substr((string) (static::query()->max('card_no') ?? 'DC-0000'), -4);

        return 'DC-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getTypeLabel(): string
    {
        return static::types()[$this->card_type] ?? $this->card_type;
    }

    public function getTypeColor(): string
    {
        return match ($this->card_type) {
            'camp_invitation' => 'purple',
            'fundraising' => 'info',
            'thank_you' => 'success',
            'birthday' => 'warning',
            'christmas' => 'danger',
            default => 'neutral',
        };
    }

    public function getStatusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'neutral',
            'active' => 'success',
            'closed' => 'danger',
            default => 'neutral',
        };
    }

    public function getPublicUrlAttribute(): string
    {
        return route('cards.show', $this->hash);
    }

    public function getProgressPercentAttribute(): float
    {
        if (! $this->target_amount || $this->target_amount <= 0) {
            return 0;
        }

        return min(100, ($this->total_contributions / $this->target_amount) * 100);
    }
}
