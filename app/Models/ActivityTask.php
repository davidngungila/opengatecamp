<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityTask extends Model
{
    protected $table = 'activity_tasks';

    protected $fillable = [
        'task_no', 'title', 'description', 'category',
        'event_id', 'assignee_id', 'assignee_name', 'assigned_by_id',
        'deadline', 'priority', 'status', 'progress',
        'challenges', 'way_forward',
        'completed_at', 'closed_at', 'closed_by', 'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'progress' => 'integer',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function event() { return $this->belongsTo(Event::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function assignees()
    {
        return $this->belongsToMany(User::class, 'activity_task_assignees', 'activity_task_id', 'user_id')
            ->withPivot('assigned_by_id')
            ->withTimestamps();
    }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by_id'); }
    public function updates() { return $this->hasMany(TaskUpdate::class, 'activity_task_id')->latest('created_at'); }

    public function syncAssignees(array $userIds, ?int $assignedById = null): void
    {
        $assignedById = $assignedById ?? auth()->id();
        $rows = [];
        foreach (array_values(array_unique(array_filter($userIds))) as $uid) {
            $rows[$uid] = [
                'user_name'      => User::find($uid)?->name,
                'assigned_by_id' => $assignedById,
            ];
        }
        $this->assignees()->sync($rows);
    }

    public static function statuses(): array
    {
        return [
            'open'           => 'Open',
            'in_progress'    => 'In Progress',
            'pending_review' => 'Pending Review',
            'completed'      => 'Completed',
            'closed'         => 'Closed',
            'cancelled'      => 'Cancelled',
        ];
    }

    public static function priorities(): array
    {
        return ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    }

    public static function categories(): array
    {
        return [
            'Logistics' => 'Logistics',
            'Venue & Accommodation' => 'Venue & Accommodation',
            'Finance & Fundraising' => 'Finance & Fundraising',
            'Communications & PR' => 'Communications & PR',
            'Spiritual Programme' => 'Spiritual Programme',
            'Meals & Catering' => 'Meals & Catering',
            'Transport' => 'Transport',
            'Health & Safety' => 'Health & Safety',
            'Volunteers' => 'Volunteers',
            'Other' => 'Other',
        ];
    }

    public static function nextTaskNo(): string
    {
        $max = (int) substr((string) (static::query()->max('task_no') ?? 'ACT-0000'), -4);
        return 'ACT-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function getStatusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'open'           => 'neutral',
            'in_progress'    => 'info',
            'pending_review' => 'warning',
            'completed'      => 'success',
            'closed'         => 'purple',
            'cancelled'      => 'danger',
            default          => 'neutral',
        };
    }

    public function getPriorityLabel(): string
    {
        return static::priorities()[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            'low'    => 'neutral',
            'medium' => 'info',
            'high'   => 'warning',
            'urgent' => 'danger',
            default  => 'neutral',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline
            && $this->deadline->isBefore(now()->startOfDay())
            && ! in_array($this->status, ['completed', 'closed', 'cancelled'], true);
    }

    public function isOpenForWork(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }
}