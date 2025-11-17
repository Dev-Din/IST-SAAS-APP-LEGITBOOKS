<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan',
        'trial_ends_at',
        'next_billing_at',
        'status',
        'vat_applied',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'next_billing_at' => 'datetime',
            'vat_applied' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
