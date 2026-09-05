<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalCardRecipient extends Model
{
    protected $fillable = [
        'digital_card_id', 'name', 'phone', 'token', 'sent_at',
        'status', 'message_id', 'delivery_status', 'delivery_checked_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivery_checked_at' => 'datetime',
    ];

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