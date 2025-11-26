<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'contact_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'notes',
        'sent_at',
        'pdf_path',
        'payment_token',
        'payment_status',
        'mail_status',
        'mail_message_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
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
        return $this->hasMany(InvoiceLineItem::class);
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
