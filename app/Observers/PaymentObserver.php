<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\PaymentService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        // Skip journal entry creation for:
        // 1. Pending payments (wait for confirmation)
        // 2. Subscription payments without invoice (will be handled in callback)
        if ($payment->transaction_status === 'pending') {
            return;
        }

        // Only process completed payments
        // For subscription payments, journal entries will be created in the callback handler
        // For invoice payments, process normally
        if ($payment->transaction_status === 'completed' && $payment->invoice_id) {
            app(PaymentService::class)->processPayment($payment, []);
        }
    }
}
