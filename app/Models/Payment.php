<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'payment_date',
        'account_id',
        'contact_id',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'mpesa_metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'mpesa_metadata' => 'array',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'reference', 'reference_type', 'reference_id');
    }
}
