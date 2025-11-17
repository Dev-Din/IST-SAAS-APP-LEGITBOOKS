<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FixedAsset extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'asset_code',
        'chart_of_account_id',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'useful_life_years',
        'depreciation_method',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'current_value' => 'decimal:2',
        ];
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
