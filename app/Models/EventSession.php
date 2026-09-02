<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    protected $fillable = [
        'event_id', 'title', 'description', 'session_date', 'start_time',
        'end_time', 'venue', 'speaker', 'facilitator', 'sort_order', 'category',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function event() { return $this->belongsTo(Event::class); }
}
