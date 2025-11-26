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
        'started_at',
        'ends_at',
        'trial_ends_at',
        'next_billing_at',
        'status',
        'vat_applied',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get masked payment method display for the subscription
     */
    public function getMaskedPaymentDisplay(): ?string
    {
        if (!$this->payment_gateway) {
            return null;
        }

        switch ($this->payment_gateway) {
            case 'mpesa':
                // Mask phone number: 254712345678 -> 254***45678
                $phone = $this->settings['phone_number'] ?? null;
                if ($phone && strlen($phone) > 6) {
                    $masked = substr($phone, 0, 3) . '***' . substr($phone, -5);
                    return 'M-Pesa (' . $masked . ')';
                }
                return 'M-Pesa';
            case 'debit_card':
            case 'credit_card':
                // Mask card number: show last 4 digits
                $cardNumber = $this->settings['card_number'] ?? null;
                if ($cardNumber && strlen($cardNumber) >= 4) {
                    $masked = '**** **** **** ' . substr($cardNumber, -4);
                    return ucfirst(str_replace('_', ' ', $this->payment_gateway)) . ' (' . $masked . ')';
                }
                return ucfirst(str_replace('_', ' ', $this->payment_gateway));
            case 'paypal':
                // Mask email: demo@paypal.com -> d***@paypal.com
                $email = $this->settings['email'] ?? null;
                if ($email) {
                    $parts = explode('@', $email);
                    if (count($parts) === 2) {
                        $local = $parts[0];
                        $domain = $parts[1];
                        $maskedLocal = strlen($local) > 2 ? substr($local, 0, 1) . '***' : '***';
                        $masked = $maskedLocal . '@' . $domain;
                        return 'PayPal (' . $masked . ')';
                    }
                }
                return 'PayPal';
            default:
                return ucfirst(str_replace('_', ' ', $this->payment_gateway));
        }
    }
}
