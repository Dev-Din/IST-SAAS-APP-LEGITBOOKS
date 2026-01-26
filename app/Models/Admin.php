<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method static \Database\Factories\AdminFactory factory($count = null, $state = [])
 */
class Admin extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $guard = 'admin';

    protected $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * @deprecated Use isOwner() instead
     */
    public function isSuperAdmin(): bool
    {
        return $this->isOwner();
    }

    public function platformAuditLogs()
    {
        return $this->hasMany(PlatformAuditLog::class);
    }

    /**
     * Check if admin has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Owners have all permissions
        if ($this->isOwner()) {
            return true;
        }

        // Check via Spatie permissions
        return $this->hasPermissionTo($permission, 'admin');
    }

    /**
     * Assign permissions to admin
     */
    public function assignPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            // Create permission if it doesn't exist
            $permissionModel = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'admin']
            );

            // Assign permission if not already assigned
            // Use the Permission model object to avoid guard issues
            if (! $this->hasPermissionTo($permissionModel)) {
                $this->givePermissionTo($permissionModel);
            }
        }
    }

    /**
     * Get all permissions as array of strings
     */
    public function getPermissionStrings(): array
    {
        return $this->getAllPermissions()
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get invitations sent by this admin
     */
    public function sentInvitations()
    {
        return $this->hasMany(AdminInvitation::class, 'inviter_admin_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AdminFactory::new();
    }
}
