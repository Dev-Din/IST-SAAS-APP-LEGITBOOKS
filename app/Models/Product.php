<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
        'sales_account_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salesAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'sales_account_id');
    }

    public function invoiceLineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }
}
