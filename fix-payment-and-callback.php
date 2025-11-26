<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Account;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

echo "Fixing Payment and Processing Callback\n";
echo "======================================\n\n";

$checkoutRequestId = 'ws_CO_26112025231735650719286858';
$tenantId = 24; // Update this if needed

// Find or create payment
$payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

if (!$payment) {
    echo "Payment not found. Creating payment record...\n";
    
    $subscription = Subscription::where('tenant_id', $tenantId)->first();
    if (!$subscription) {
        echo "❌ Subscription not found for tenant {$tenantId}\n";
        exit(1);
    }
    
    // Get or create M-Pesa account
    $mpesaAccount = Account::where('tenant_id', $tenantId)
        ->where('type', 'mpesa')
        ->first();
    
    if (!$mpesaAccount) {
        $cashAccount = ChartOfAccount::where('tenant_id', $tenantId)
            ->where('code', '1400')
            ->first();
        
        if ($cashAccount) {
            $mpesaAccount = Account::create([
                'tenant_id' => $tenantId,
                'name' => 'M-Pesa',
                'type' => 'mpesa',
                'chart_of_account_id' => $cashAccount->id,
                'is_active' => true,
            ]);
        }
    }
    
    $payment = Payment::create([
        'tenant_id' => $tenantId,
        'subscription_id' => $subscription->id,
        'payment_number' => 'SUB-' . date('Ymd') . '-FIX',
        'payment_date' => now()->toDateString(),
        'account_id' => $mpesaAccount->id ?? null,
        'amount' => 2500,
        'payment_method' => 'mpesa',
        'phone' => '254719286858',
        'transaction_status' => 'pending',
        'checkout_request_id' => $checkoutRequestId,
        'merchant_request_id' => '65c1-4675-96a1-ce3150ced5c6950',
    ]);
    
    echo "✅ Payment created: ID {$payment->id}\n\n";
}

echo "Processing callback...\n";

DB::transaction(function () use ($payment) {
    // Update payment as completed
    $payment->update([
        'transaction_status' => 'completed',
        'mpesa_receipt' => 'FIX-' . date('YmdHis'),
        'reference' => 'FIX-' . date('YmdHis'),
    ]);
    
    // Activate subscription
    if ($payment->subscription_id) {
        $subscription = $payment->subscription;
        $subscription->update([
            'status' => 'active',
            'plan' => 'starter',
            'payment_gateway' => 'mpesa',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
            'settings' => ['phone_number' => '254719286858'],
        ]);
        
        echo "✅ Subscription activated!\n";
        echo "   Plan: {$subscription->plan}\n";
        echo "   Status: {$subscription->status}\n";
    }
});

echo "\n✅ Done! Payment processed and subscription activated.\n";

