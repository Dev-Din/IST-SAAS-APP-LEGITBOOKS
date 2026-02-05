<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'tenant_id',
        'role_id',
        'role_name',
        'permissions',
        'is_active',
        'is_owner',
        'phone_country_code',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_owner' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function invitations()
    {
        return $this->hasMany(UserInvitation::class, 'inviter_user_id');
    }

    /**
     * Check if user has a specific permission
     * Account owners always have full access
     */
    public function hasPermission(string $permission): bool
    {
        // Owner always has full access
        if ($this->is_owner) {
            return true;
        }

        $permissions = is_array($this->permissions) ? $this->permissions : [];

        return in_array($permission, $permissions, true);
    }

    /**
     * Check if user has any of the given permissions
     * Account owners always have full access
     */
    public function hasAnyPermission(array $permissions): bool
    {
        // Owner always has full access
        if ($this->is_owner) {
            return true;
        }

        $current = is_array($this->permissions) ? $this->permissions : [];

        return count(array_intersect($permissions, $current)) > 0;
    }

    /**
     * Send the password reset notification (tenant panel reset URL).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\TenantResetPasswordNotification($token));
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        }

        return $this->name ?? '';
    }
}
