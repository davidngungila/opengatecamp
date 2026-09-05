<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'message', 'created_by'];

    public static function defaultTemplates(): array
    {
        return [
            ['Welcome Message', 'Hi {name}, welcome to OpenGate Camp Connect! We are glad to have you.'],
            ['Event Reminder', 'Reminder: {event} takes place on {date} at {venue}.'],
            ['Pledge Reminder', 'Dear {name}, your pledge balance for {campaign} is {balance}.'],
            ['Birthday Wish', 'Happy birthday {name}! May God bless you abundantly.'],
            ['Follow-up', 'Hi {name}, thank you for visiting us. We would love to see you again.'],
            ['Thank You', 'Thank you {name} for your generous contribution of {amount}.'],
            ['Mass Intention', 'Dear {name}, we have received your mass intention for {date}. Please keep us in your prayers.'],
            ['Sacrament Reminder', 'Dear {name}, this is a reminder about your upcoming {sacrament} on {date}.'],
        ];
    }
}