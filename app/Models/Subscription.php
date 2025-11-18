<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan',
        'payment_gateway',
        'trial_ends_at',
        'next_billing_at',
        'status',
        'vat_applied',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'vat_applied' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
