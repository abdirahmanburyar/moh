<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'module',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'guard_name' => 'string',
    ];

    /**
     * Get the users that have this permission.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_user')
                    ->withTimestamps();
    }

    /**
     * Scope to filter by module.
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get permissions grouped by module.
     */
    public static function getGroupedByModule()
    {
        return static::orderBy('module')
                    ->orderBy('display_name')
                    ->get()
                    ->groupBy('module');
    }

    /**
     * Check if permission is for a specific module.
     */
    public function isForModule($module)
    {
        return $this->module === $module;
    }
}
