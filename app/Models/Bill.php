<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bill extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'bill_number',
        'contact_id',
        'bill_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function lineItems()
    {
        return $this->hasMany(BillLineItem::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'reference', 'reference_type', 'reference_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getOutstandingAmount(): float
    {
        $allocated = $this->paymentAllocations()->sum('amount');
        return max(0, $this->total - $allocated);
    }
}

