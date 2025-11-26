<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MpesaStkService;
use Illuminate\Support\Facades\Log;

echo "Testing M-Pesa STK Push\n";
echo "======================\n\n";

// Phone number to test
$phoneNumber = '254719286858';
$amount = 100.00; // Test amount

echo "Phone Number: {$phoneNumber}\n";
echo "Amount: KES {$amount}\n";
echo "Test Amount (STK Push): KES 1.00 (development mode)\n\n";

try {
    $mpesaService = app(MpesaStkService::class);
    
    // Check if configured
    if (!$mpesaService->isConfigured()) {
        echo "❌ ERROR: M-Pesa is not configured!\n";
        echo "Please set MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_PASSKEY, and MPESA_SHORTCODE in your .env file.\n";
        exit(1);
    }
    
    echo "✓ M-Pesa service is configured\n";
    
    // Test access token
    echo "\nGetting access token...\n";
    $accessToken = $mpesaService->getAccessToken();
    echo "✓ Access token obtained: " . substr($accessToken, 0, 20) . "...\n\n";
    
    // Initiate STK Push
    echo "Initiating STK Push...\n";
    $result = $mpesaService->initiateSTKPush([
        'phone_number' => $phoneNumber,
        'amount' => $amount,
        'account_reference' => 'TEST-' . date('YmdHis'),
        'transaction_desc' => 'Test STK Push Payment',
    ]);
    
    if ($result['success']) {
        echo "\n✅ SUCCESS! STK Push initiated successfully!\n\n";
        echo "Checkout Request ID: {$result['checkoutRequestID']}\n";
        echo "Merchant Request ID: {$result['merchantRequestID']}\n";
        echo "Customer Message: {$result['customerMessage']}\n";
        echo "\n📱 Please check your phone ({$phoneNumber}) to complete the payment.\n";
        echo "You should receive an M-Pesa prompt for KES 1.00 (test amount).\n";
    } else {
        echo "\n❌ FAILED to initiate STK Push!\n\n";
        echo "Error: {$result['error']}\n";
        if (isset($result['response_code'])) {
            echo "Response Code: {$result['response_code']}\n";
        }
        if (isset($result['response'])) {
            echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
echo "Check logs at: storage/logs/laravel.log\n";

