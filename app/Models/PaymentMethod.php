<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'is_default',
        'is_active',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'details' => 'array',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get masked card number for display
     */
    public function getMaskedCardNumber(): ?string
    {
        if ($this->type === 'debit_card' || $this->type === 'credit_card') {
            $cardNumber = $this->details['card_number'] ?? '';
            if (strlen($cardNumber) >= 4) {
                return '**** **** **** '.substr($cardNumber, -4);
            }
        }

        return null;
    }

    /**
     * Get display name for the payment method
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        switch ($this->type) {
            case 'mpesa':
                return 'M-Pesa ('.($this->details['phone_number'] ?? 'N/A').')';
            case 'debit_card':
            case 'credit_card':
                return ucfirst(str_replace('_', ' ', $this->type)).' ('.$this->getMaskedCardNumber().')';
            case 'paypal':
                return 'PayPal ('.($this->details['email'] ?? 'N/A').')';
            default:
                return ucfirst(str_replace('_', ' ', $this->type));
        }
    }
}
