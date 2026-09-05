<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalCardRecipient extends Model
{
    protected $fillable = [
        'digital_card_id', 'name', 'phone', 'token', 'short_code',
        'sent_at', 'status', 'message_id', 'message', 'delivery_status',
        'delivery_checked_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivery_checked_at' => 'datetime',
    ];

    protected static function uniqueCode(int $length = 8, string $field = 'short_code'): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, $length));
        } while (static::where($field, $code)->exists());

        return $code;
    }

    public function getShortLinkAttribute(): string
    {
        if (! $this->short_code) {
            $this->short_code = static::uniqueCode();
            if ($this->exists) {
                $this->save();
            }
        }

        return route('cards.lite', $this->short_code);
    }

    public static function booted(): void
    {
        static::creating(function (DigitalCardRecipient $recipient) {
            if (! $recipient->token) {
                $recipient->token = static::uniqueCode(10, 'token');
            }
            if (! $recipient->short_code) {
                $recipient->short_code = static::uniqueCode();
            }
        });
    }

    public function card()
    {
        return $this->belongsTo(DigitalCard::class);
    }

    public function digitalCard()
    {
        return $this->belongsTo(DigitalCard::class);
    }

    public static function inviteStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'invited' => 'Invited',
            'failed'  => 'Failed',
        ];
    }

    public function getInviteStatusColor(): string
    {
        return match ($this->status) {
            'invited' => 'success',
            'failed'  => 'danger',
            default   => 'neutral',
        };
    }

    public function getDeliveryStatusColor(): string
    {
        return match ($this->delivery_status) {
            'delivered'  => 'success',
            'undelivered'=> 'danger',
            'pending'    => 'warning',
            default      => 'neutral',
        };
    }
}