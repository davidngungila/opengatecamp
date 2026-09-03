<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'user_name', 'action', 'module', 'details', 'ip', 'is_read'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public static function unreadCount(): int
    {
        return static::where('is_read', false)->count();
    }

    public static function markAllRead(): int
    {
        return static::where('is_read', false)->update(['is_read' => true]);
    }

    public static function record(string $action, ?string $module = null, ?string $details = null): void
    {
        $user = auth()->user();

        static::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'Daniel Mwinuka',
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
