<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'status', 'last_login_at', 'member_id', 'profile_image', 'bio',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function role() { return $this->belongsTo(Role::class); }
    public function member() { return $this->belongsTo(Member::class); }

    public function hasRole(string $name): bool
    {
        return $this->role?->name === $name;
    }

    public function isCommitteeMember(): bool
    {
        return in_array($this->role?->name, ['Committee Member', 'committee'], true);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role?->name === 'Super Administrator') {
            return true;
        }

        return in_array($permission, $this->role?->permissions ?? [], true);
    }
}
