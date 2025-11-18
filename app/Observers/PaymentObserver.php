<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\PaymentService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        app(PaymentService::class)->processPayment($payment, []);
    }
}
