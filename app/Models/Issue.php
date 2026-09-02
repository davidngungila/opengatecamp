<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'category', 'title', 'description', 'status', 'priority',
        'assignee', 'reported_by', 'resolution', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return ['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
    }

    public static function priorities(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    public function getStatusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'open'        => 'danger',
            'in_progress' => 'warning',
            'resolved'    => 'success',
            'closed'      => 'neutral',
            default       => 'neutral',
        };
    }
}