<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalCardRecipient extends Model
{
    protected $fillable = [
        'digital_card_id', 'name', 'phone', 'token', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(DigitalCard::class);
    }
}
