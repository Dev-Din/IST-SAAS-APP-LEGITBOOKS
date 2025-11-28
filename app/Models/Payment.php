<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'invoice_id',
        'subscription_id',
        'payment_number',
        'payment_date',
        'account_id',
        'contact_id',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'notes',
        'mpesa_metadata',
        'phone',
        'mpesa_receipt',
        'transaction_status',
        'raw_callback',
        'checkout_request_id',
        'merchant_request_id',
        'client_token',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'mpesa_metadata' => 'array',
            'raw_callback' => 'array',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate client_token if not provided
        static::creating(function ($payment) {
            if (empty($payment->client_token)) {
                $payment->client_token = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
