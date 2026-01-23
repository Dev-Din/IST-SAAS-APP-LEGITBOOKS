<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'inviter_admin_id',
        'first_name',
        'last_name',
        'email',
        'role_name',
        'permissions',
        'token',
        'temp_password_hash',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Generate a secure invitation token
     */
    public static function generateToken(): string
    {
        return Str::random(60);
    }

    /**
     * Generate a temporary password
     */
    public static function generateTempPassword(): string
    {
        return Str::random(16);
    }

    /**
     * Scope to get pending invitations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Check if invitation is valid (pending and not expired)
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    /**
     * Get the inviter admin
     */
    public function inviter()
    {
        return $this->belongsTo(Admin::class, 'inviter_admin_id');
    }

    /**
     * Get the tenant (if applicable)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
