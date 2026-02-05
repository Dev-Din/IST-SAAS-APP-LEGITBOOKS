<?php
/**
 * One-off script to trigger M-Pesa STK push for an unpaid invoice.
 * Usage: php test-invoice-mpesa.php [invoice_id] [phone]
 * Default phone: 254719286858
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invoiceId = isset($argv[1]) ? (int) $argv[1] : null;
$phone = $argv[2] ?? '254719286858';

$invoice = \App\Models\Invoice::withoutGlobalScope('tenant')
    ->when($invoiceId, fn ($q) => $q->where('id', $invoiceId))
    ->where('status', '!=', 'paid')
    ->first();

if (! $invoice) {
    echo "No unpaid invoice found." . PHP_EOL;
    exit(1);
}

if (! $invoice->payment_token) {
    $invoice->payment_token = \Illuminate\Support\Str::random(64);
    $invoice->save();
}

$payUrl = url("/pay/{$invoice->id}/{$invoice->payment_token}");
echo "Invoice: {$invoice->invoice_number} (ID: {$invoice->id})" . PHP_EOL;
echo "Payment URL: {$payUrl}" . PHP_EOL;
echo "Triggering STK push to {$phone}..." . PHP_EOL;

$request = \Illuminate\Http\Request::create(
    "/pay/{$invoice->id}/{$invoice->payment_token}/mpesa",
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json'],
    json_encode(['phone_number' => $phone])
);
$request->headers->set('Accept', 'application/json');

$controller = app(\App\Http\Controllers\InvoicePaymentController::class);
try {
    $response = $controller->processMpesa($request, $invoice->id, $invoice->payment_token);
    $data = json_decode($response->getContent(), true);
    if ($data['success'] ?? false) {
        $successUrl = url("/pay/{$invoice->id}/{$invoice->payment_token}/success") . '?checkout_request_id=' . urlencode($data['checkoutRequestID'] ?? '');
        echo "STK push sent. Check your phone and enter PIN." . PHP_EOL;
        echo "Success page (after you pay): {$successUrl}" . PHP_EOL;
    } else {
        echo "Error: " . ($data['error'] ?? 'Unknown') . PHP_EOL;
        exit(1);
    }
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
