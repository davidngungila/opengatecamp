<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const PERMISSIONS = [
        'members.view', 'members.manage',
        'events.manage', 'events.complete',
        'pledges.manage',
        'finance.view', 'finance.manage', 'finance.approve',
        'communication.send',
        'documents.view', 'documents.manage',
        'reports.view', 'reports.export',
        'users.manage', 'roles.manage', 'settings.manage', 'audit.view',
    ];

    protected $fillable = ['name', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public function users() { return $this->hasMany(User::class); }

    public function getIsSuperAttribute(): bool
    {
        return $this->name === 'Super Administrator';
    }
}
