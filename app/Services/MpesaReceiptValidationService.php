<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaReceiptValidationService
{
    protected ?string $consumerKey;

    protected ?string $consumerSecret;

    protected ?string $baseUrl;

    protected ?string $shortcode;

    protected ?string $passkey;

    public function __construct()
    {
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->baseUrl = config('mpesa.base_url', 'https://sandbox.safaricom.co.ke');
        $this->shortcode = config('mpesa.shortcode');
        $this->passkey = config('mpesa.passkey');
    }

    /**
     * Check if M-Pesa is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->consumerKey)
            && ! empty($this->consumerSecret)
            && ! empty($this->shortcode)
            && ! empty($this->passkey);
    }

    /**
     * Get access token from M-Pesa OAuth endpoint
     */
    public function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            Log::error('M-Pesa credentials not configured');

            return null;
        }

        // Check cache first
        $cacheKey = config('mpesa.token_cache_key', 'mpesa_access_token');
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            $authUrl = $this->baseUrl.'/oauth/v1/generate?grant_type=client_credentials';

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($authUrl);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? null;

                if ($accessToken) {
                    // Cache token for 55 minutes (tokens expire in 1 hour)
                    $ttl = config('mpesa.token_cache_ttl', 3300);
                    Cache::put($cacheKey, $accessToken, $ttl);

                    Log::info('M-Pesa access token obtained');

                    return $accessToken;
                }
            }

            Log::error('Failed to get M-Pesa access token', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception getting M-Pesa access token', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Query transaction status using M-Pesa Receipt Number
     *
     * @param  string  $receiptNumber  M-Pesa receipt number (e.g., "TEST12345")
     * @return array|null Transaction details or null if not found/invalid
     */
    public function queryTransactionStatus(string $receiptNumber): ?array
    {
        if (! $this->isConfigured()) {
            Log::error('M-Pesa credentials not configured for transaction query');

            return null;
        }

        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::error('Failed to get access token for transaction query');

            return null;
        }

        try {
            $queryUrl = $this->baseUrl.'/mpesa/transactionstatus/v1/query';

            // Generate password (Base64 encoded)
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode.$this->passkey.$timestamp);

            $payload = [
                'Initiator' => $this->shortcode,
                'SecurityCredential' => $this->generateSecurityCredential(),
                'CommandID' => 'TransactionStatusQuery',
                'TransactionID' => $receiptNumber,
                'PartyA' => $this->shortcode,
                'IdentifierType' => '4', // Organization
                'ResultURL' => config('mpesa.callback_url', '').'/transaction-status-result',
                'QueueTimeOutURL' => config('mpesa.callback_url', '').'/transaction-status-timeout',
                'Remarks' => 'Payment receipt validation',
                'Occasion' => 'Receipt Validation',
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($queryUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('M-Pesa transaction status query sent', [
                    'receipt' => $receiptNumber,
                    'response' => $data,
                ]);

                return $data;
            }

            Log::error('Failed to query M-Pesa transaction status', [
                'receipt' => $receiptNumber,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception querying M-Pesa transaction status', [
                'receipt' => $receiptNumber,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Validate payment receipt by querying M-Pesa API
     *
     * @param  Payment  $payment  Payment record to validate
     * @return array Validation result with status and details
     */
    public function validatePaymentReceipt(Payment $payment): array
    {
        if (! $payment->mpesa_receipt) {
            return [
                'valid' => false,
                'error' => 'No M-Pesa receipt number found',
                'payment_id' => $payment->id,
            ];
        }

        $receiptNumber = $payment->mpesa_receipt;

        // Query transaction status
        $transactionData = $this->queryTransactionStatus($receiptNumber);

        if (! $transactionData) {
            return [
                'valid' => false,
                'error' => 'Failed to query transaction status from M-Pesa',
                'payment_id' => $payment->id,
                'receipt' => $receiptNumber,
            ];
        }

        // Check if transaction exists and is successful
        $responseCode = $transactionData['ResponseCode'] ?? null;
        $responseDescription = $transactionData['ResponseDescription'] ?? '';

        if ($responseCode == '0') {
            // Transaction found and valid
            return [
                'valid' => true,
                'payment_id' => $payment->id,
                'receipt' => $receiptNumber,
                'transaction_data' => $transactionData,
                'message' => 'Payment receipt validated successfully',
            ];
        } else {
            return [
                'valid' => false,
                'error' => $responseDescription ?: 'Transaction not found or invalid',
                'payment_id' => $payment->id,
                'receipt' => $receiptNumber,
                'response_code' => $responseCode,
            ];
        }
    }

    /**
     * Fetch and validate payment by receipt number
     *
     * @param  string  $receiptNumber  M-Pesa receipt number
     * @return array|null Payment details or null if not found
     */
    public function fetchPaymentByReceipt(string $receiptNumber): ?array
    {
        // First, try to find payment in database
        $payment = Payment::where('mpesa_receipt', $receiptNumber)->first();

        if ($payment) {
            $validation = $this->validatePaymentReceipt($payment);

            return [
                'payment' => $payment,
                'validation' => $validation,
                'found_in_db' => true,
            ];
        }

        // If not in database, query M-Pesa API
        $transactionData = $this->queryTransactionStatus($receiptNumber);

        if ($transactionData && ($transactionData['ResponseCode'] ?? null) == '0') {
            return [
                'payment' => null,
                'transaction_data' => $transactionData,
                'found_in_db' => false,
                'found_in_mpesa' => true,
            ];
        }

        return null;
    }

    /**
     * Validate all pending payments for a tenant
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Validation results
     */
    public function validatePendingPayments(int $tenantId): array
    {
        $pendingPayments = Payment::where('tenant_id', $tenantId)
            ->where('payment_method', 'mpesa')
            ->where('transaction_status', 'pending')
            ->whereNotNull('mpesa_receipt')
            ->get();

        $results = [];

        foreach ($pendingPayments as $payment) {
            $validation = $this->validatePaymentReceipt($payment);
            $results[] = [
                'payment_id' => $payment->id,
                'receipt' => $payment->mpesa_receipt,
                'validation' => $validation,
            ];
        }

        return $results;
    }

    /**
     * Generate security credential (simplified - in production, use proper encryption)
     * For sandbox, this might not be required, but for production you need to encrypt
     */
    protected function generateSecurityCredential(): string
    {
        // In production, this should be encrypted using the M-Pesa public key
        // For sandbox, you might be able to use a simpler approach
        // This is a placeholder - implement proper encryption for production

        if (config('app.env') === 'production') {
            // TODO: Implement proper security credential encryption
            Log::warning('Security credential encryption not implemented for production');
        }

        // For sandbox, you might not need this or it might be different
        return base64_encode($this->shortcode.$this->passkey);
    }
}
