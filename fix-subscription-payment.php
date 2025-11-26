<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Fixing Subscription Payment\n";
echo "===========================\n\n";

// Find the latest pending payment for subscription
$payment = Payment::where('transaction_status', 'pending')
    ->whereNotNull('subscription_id')
    ->orWhere(function($query) {
        $query->where('transaction_status', 'pending')
              ->where('amount', 2500) // Starter plan
              ->where('payment_method', 'mpesa');
    })
    ->latest()
    ->first();

if (!$payment) {
    echo "❌ No pending subscription payment found.\n";
    echo "Please provide the payment ID or checkout request ID.\n";
    exit(1);
}

echo "Found Payment:\n";
echo "  ID: {$payment->id}\n";
echo "  Amount: {$payment->amount}\n";
echo "  Status: {$payment->transaction_status}\n";
echo "  Subscription ID: " . ($payment->subscription_id ?? 'N/A') . "\n";
echo "  Checkout Request ID: " . ($payment->checkout_request_id ?? 'N/A') . "\n\n";

if (!$payment->subscription_id) {
    // Try to find subscription by tenant
    $subscription = Subscription::where('tenant_id', $payment->tenant_id)
        ->where('plan', 'starter')
        ->first();
    
    if ($subscription) {
        echo "Linking payment to subscription {$subscription->id}...\n";
        $payment->update(['subscription_id' => $subscription->id]);
    }
}

// Simulate successful payment
echo "Simulating successful payment...\n";

DB::transaction(function () use ($payment) {
    // Update payment
    $payment->update([
        'transaction_status' => 'completed',
        'mpesa_receipt' => 'MANUAL-' . date('YmdHis'),
        'reference' => 'MANUAL-' . date('YmdHis'),
    ]);

    // Activate subscription
    if ($payment->subscription_id) {
        $subscription = $payment->subscription;
        $subscription->update([
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
        ]);
        
        echo "✅ Subscription activated!\n";
        echo "  Subscription ID: {$subscription->id}\n";
        echo "  Plan: {$subscription->plan}\n";
        echo "  Status: {$subscription->status}\n";
    }
});

echo "\n✅ Payment processed successfully!\n";
echo "The subscription should now be active.\n";

