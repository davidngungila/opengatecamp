<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'user_name', 'action', 'module', 'details', 'ip'];

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
