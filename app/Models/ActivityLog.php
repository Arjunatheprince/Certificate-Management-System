<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'target', 'ip_address', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $target = null, ?string $description = null): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'target'      => $target,
            'ip_address'  => request()->ip(),
            'description' => $description,
        ]);
    }

    public function getActionBadgeAttribute(): string
    {
        return match($this->action) {
            'login'       => 'bg-green-100 text-green-700',
            'logout'      => 'bg-gray-100 text-gray-700',
            'upload'      => 'bg-blue-100 text-blue-700',
            'edit'        => 'bg-amber-100 text-amber-700',
            'delete'      => 'bg-red-100 text-red-700',
            'create_user' => 'bg-purple-100 text-purple-700',
            'update_user' => 'bg-yellow-100 text-yellow-700',
            'delete_user' => 'bg-red-100 text-red-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }
}
