<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden   = ['password'];

    protected $casts = ['is_active' => 'boolean'];

    public function isSuperAdmin(): bool { return $this->role === 'superadmin'; }
    public function isAdmin(): bool      { return $this->role === 'admin'; }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'Super Admin',
            'admin'      => 'Admin',
            default      => ucfirst($this->role),
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'superadmin' => 'bg-red-100 text-red-700',
            'admin'      => 'bg-blue-100 text-blue-700',
            default      => 'bg-gray-100 text-gray-700',
        };
    }
}
