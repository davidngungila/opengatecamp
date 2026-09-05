<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'channel', 'recipients', 'phone', 'subject', 'message',
        'status', 'api_message_id', 'api_response', 'created_by',
        'delivery_status', 'delivery_checked_at',
    ];

    protected $casts = [
        'api_response' => 'array',
        'delivery_checked_at' => 'datetime',
    ];
}
