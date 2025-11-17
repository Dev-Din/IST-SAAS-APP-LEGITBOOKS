<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'account_number',
        'chart_of_account_id',
        'opening_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
