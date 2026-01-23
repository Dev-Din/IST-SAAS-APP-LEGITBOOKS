<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function processCallback(array $payload): ?Payment
    {
        $tenant = $this->tenantContext->getTenant();

        if (! $tenant) {
            Log::error('M-Pesa callback: Tenant context not set');

            return null;
        }

        // Validate M-Pesa callback payload
        // This is a simplified version - in production, add proper validation
        $phone = $payload['PhoneNumber'] ?? null;
        $amount = $payload['TransAmount'] ?? null;
        $transactionId = $payload['TransID'] ?? null;
        $reference = $payload['BillRefNumber'] ?? null;

        if (! $phone || ! $amount || ! $transactionId) {
            Log::error('M-Pesa callback: Missing required fields', $payload);

            return null;
        }

        // Find or create M-Pesa account
        $mpesaAccount = Account::where('tenant_id', $tenant->id)
            ->where('type', 'mpesa')
            ->first();

        if (! $mpesaAccount) {
            // Create M-Pesa account if it doesn't exist
            $cashAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                ->where('code', '1400')
                ->first();

            if ($cashAccount) {
                $mpesaAccount = Account::create([
                    'tenant_id' => $tenant->id,
                    'name' => 'M-Pesa',
                    'type' => 'mpesa',
                    'chart_of_account_id' => $cashAccount->id,
                    'is_active' => true,
                ]);
            }
        }

        // Create payment
        $paymentNumber = 'PAY-'.date('Ymd').'-'.str_pad(Payment::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'payment_number' => $paymentNumber,
            'payment_date' => now()->toDateString(),
            'account_id' => $mpesaAccount->id,
            'amount' => $amount,
            'payment_method' => 'mpesa',
            'reference' => $transactionId,
            'notes' => "M-Pesa payment from {$phone}",
            'mpesa_metadata' => $payload,
        ]);

        return $payment;
    }

    public function simulatePayment(string $phone, float $amount): array
    {
        // Simulate M-Pesa payment for development
        $payload = [
            'PhoneNumber' => $phone,
            'TransAmount' => $amount,
            'TransID' => 'SIM-'.uniqid(),
            'BillRefNumber' => 'TEST',
            'TransTime' => now()->format('YmdHis'),
        ];

        $payment = $this->processCallback($payload);

        return [
            'success' => $payment !== null,
            'payment' => $payment,
            'payload' => $payload,
        ];
    }

    /**
     * Parse M-Pesa STK callback payload
     *
     * @param  array  $body  Raw callback body
     * @return array Parsed callback data
     */
    public function parseCallback(array $body): array
    {
        $stkCallback = $body['Body']['stkCallback'] ?? null;

        if (! $stkCallback) {
            return [
                'valid' => false,
                'error' => 'Missing stkCallback in payload',
            ];
        }

        $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;
        $merchantRequestID = $stkCallback['MerchantRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? null;
        $resultDesc = $stkCallback['ResultDesc'] ?? '';

        $parsed = [
            'valid' => true,
            'checkout_request_id' => $checkoutRequestID,
            'merchant_request_id' => $merchantRequestID,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'success' => $resultCode == 0,
        ];

        // Extract callback metadata if successful
        if ($resultCode == 0 && isset($stkCallback['CallbackMetadata']['Item'])) {
            $metadata = [];
            foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
                $name = $item['Name'] ?? '';
                $value = $item['Value'] ?? null;
                $metadata[$name] = $value;
            }
            $parsed['metadata'] = $metadata;
            $parsed['mpesa_receipt'] = $metadata['MpesaReceiptNumber'] ?? null;
            $parsed['amount'] = $metadata['Amount'] ?? null;
            $parsed['phone'] = $metadata['PhoneNumber'] ?? null;
            $parsed['transaction_date'] = $metadata['TransactionDate'] ?? null;
        }

        return $parsed;
    }

    /**
     * Log Cloudflare headers for debugging
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array Cloudflare headers
     */
    public function logCloudflareHeaders($request): array
    {
        $cfHeaders = [
            'CF-RAY' => $request->header('CF-RAY'),
            'cf-mitigated' => $request->header('cf-mitigated'),
            'CF-Connecting-IP' => $request->header('CF-Connecting-IP'),
            'X-Forwarded-For' => $request->header('X-Forwarded-For'),
            'X-Real-IP' => $request->header('X-Real-IP'),
        ];

        Log::info('Cloudflare headers detected', [
            'headers' => $cfHeaders,
            'ip' => $request->ip(),
        ]);

        return $cfHeaders;
    }

    /**
     * Verify callback IP address (production only)
     */
    public function verifyCallbackIP(string $ip): bool
    {
        if (! config('mpesa.validate_callback_ip', false)) {
            return true; // Skip validation in sandbox/development
        }

        $whitelist = config('mpesa.callback_ip_whitelist', []);

        return in_array($ip, $whitelist);
    }
}
