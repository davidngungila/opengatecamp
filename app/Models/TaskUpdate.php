<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskUpdate extends Model
{
    protected $table = 'task_updates';

    protected $fillable = [
        'activity_task_id', 'user_id', 'user_name', 'type',
        'content', 'progress', 'old_value', 'new_value',
    ];

    protected $casts = [
        'progress' => 'integer',
    ];

    public function task() { return $this->belongsTo(ActivityTask::class, 'activity_task_id'); }
    public function user() { return $this->belongsTo(User::class); }

    public static function types(): array
    {
        return [
            'assignment' => 'Assignment',
            'report'     => 'Progress Update',
            'status'     => 'Status Change',
        ];
    }

    public function getTypeLabel(): string
    {
        return static::types()[$this->type] ?? ucfirst((string) $this->type);
    }
}