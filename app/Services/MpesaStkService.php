<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaStkService
{
    protected ?string $baseUrl;

    protected ?string $authUrl;

    protected ?string $stkPushUrl;

    protected ?string $consumerKey;

    protected ?string $consumerSecret;

    protected ?string $passkey;

    protected ?string $shortcode;

    protected ?string $callbackUrl;

    protected ?string $transactionType;

    public function __construct()
    {
        $this->baseUrl = config('mpesa.base_url');
        $this->authUrl = config('mpesa.auth_url');
        $this->stkPushUrl = config('mpesa.stk_push_url');
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->passkey = config('mpesa.passkey');
        $this->shortcode = config('mpesa.shortcode');
        $this->callbackUrl = config('mpesa.callback_url');
        $this->transactionType = config('mpesa.transaction_type');
    }

    /**
     * Check if M-Pesa is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->consumerKey)
            && ! empty($this->consumerSecret)
            && ! empty($this->passkey)
            && ! empty($this->shortcode);
    }

    /**
     * Get access token from cache or generate new one
     */
    public function getAccessToken(): string
    {
        $cacheKey = config('mpesa.token_cache_key');
        $ttl = config('mpesa.token_cache_ttl');

        return Cache::remember($cacheKey, $ttl, function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate new access token from M-Pesa OAuth endpoint
     */
    protected function generateAccessToken(): string
    {
        $retryAttempts = config('mpesa.token_retry_attempts', 3);
        $retryDelay = config('mpesa.token_retry_delay', 1);

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                    ->get($this->authUrl);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['access_token'])) {
                        Log::info('M-Pesa access token generated successfully');

                        return $data['access_token'];
                    }
                }

                Log::warning('M-Pesa token generation failed', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                if ($attempt < $retryAttempts) {
                    sleep($retryDelay);
                }
            } catch (\Exception $e) {
                Log::error('M-Pesa token generation exception', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $retryAttempts) {
                    sleep($retryDelay);
                }
            }
        }

        throw new \Exception('Failed to generate M-Pesa access token after '.$retryAttempts.' attempts');
    }

    /**
     * Generate password for STK push (Base64 encoded)
     */
    protected function generatePassword(): string
    {
        $timestamp = $this->getTimestamp();
        $password = base64_encode($this->shortcode.$this->passkey.$timestamp);

        return $password;
    }

    /**
     * Get current timestamp in format YYYYMMDDHHmmss
     */
    protected function getTimestamp(): string
    {
        return date('YmdHis');
    }

    /**
     * Initiate STK Push request
     */
    public function initiateSTKPush(array $data): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'M-Pesa is not configured. Please set MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_PASSKEY, and MPESA_SHORTCODE in your .env file.',
            ];
        }

        $accessToken = $this->getAccessToken();
        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword();

        // Format phone number (remove + and ensure it starts with 254)
        $phoneNumber = $this->formatPhoneNumber($data['phone_number']);

        // Sandbox/testing: use KES 1 for the STK prompt. Production: use real invoice amount.
        $actualAmount = (float) $data['amount'];
        $stkPushAmount = config('app.env') === 'production'
            ? (int) round($actualAmount)
            : 1;

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transactionType,
            'Amount' => $stkPushAmount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $data['account_reference'] ?? 'INV-'.($data['invoice_id'] ?? ''),
            'TransactionDesc' => $data['transaction_desc'] ?? 'Payment for Invoice '.($data['invoice_id'] ?? ''),
        ];

        Log::info('M-Pesa STK Push request', [
            'payload' => $payload,
            'invoice_id' => $data['invoice_id'] ?? null,
        ]);

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->stkPushUrl, $payload);

            $responseData = $response->json();

            // Check for error response format (errorCode/errorMessage)
            if (isset($responseData['errorCode']) || isset($responseData['errorMessage'])) {
                $errorMessage = $responseData['errorMessage'] ?? 'M-Pesa API error';
                $errorCode = $responseData['errorCode'] ?? 'UNKNOWN';

                Log::error('M-Pesa STK Push API error', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'status' => $response->status(),
                    'response' => $responseData,
                    'callback_url' => $this->callbackUrl,
                ]);

                // Provide user-friendly error messages
                if (str_contains($errorMessage, 'Invalid CallBackURL') || str_contains($errorMessage, 'CallbackURL')) {
                    return [
                        'success' => false,
                        'error' => 'Invalid callback URL. Please ensure your Cloudflare tunnel is running and MPESA_CALLBACK_BASE is set correctly in .env',
                        'error_code' => $errorCode,
                        'callback_url' => $this->callbackUrl,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                ];
            }

            if ($response->successful() && isset($responseData['ResponseCode'])) {
                if ($responseData['ResponseCode'] == '0') {
                    Log::info('M-Pesa STK Push initiated successfully', [
                        'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
                        'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'checkoutRequestID' => $responseData['CheckoutRequestID'] ?? '',
                        'customerMessage' => $responseData['CustomerMessage'] ?? '',
                        'merchantRequestID' => $responseData['MerchantRequestID'] ?? '',
                        'response_code' => $responseData['ResponseCode'] ?? '',
                    ];
                } else {
                    Log::error('M-Pesa STK Push failed', [
                        'response_code' => $responseData['ResponseCode'] ?? '',
                        'error_message' => $responseData['ErrorMessage'] ?? '',
                        'response' => $responseData,
                    ]);

                    return [
                        'success' => false,
                        'error' => $responseData['ErrorMessage'] ?? 'STK Push request failed',
                        'response_code' => $responseData['ResponseCode'] ?? '',
                    ];
                }
            }

            Log::error('M-Pesa STK Push unexpected response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected response from M-Pesa API: '.($responseData['errorMessage'] ?? json_encode($responseData)),
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate STK Push: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to M-Pesa format (254XXXXXXXXX)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254'.substr($phone, 1);
        }

        // If doesn't start with 254, add it
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254'.$phone;
        }

        return $phone;
    }

    /**
     * Query STK Push payment status using CheckoutRequestID
     * This allows fetching payment details directly from Daraja API
     */
    public function querySTKPushStatus(string $checkoutRequestID): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'M-Pesa is not configured.',
            ];
        }

        $accessToken = $this->getAccessToken();
        $timestamp = $this->getTimestamp();
        $password = $this->generatePassword();

        $queryUrl = $this->baseUrl.'/mpesa/stkpushquery/v1/query';

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestID,
        ];

        Log::info('M-Pesa STK Push query request', [
            'checkout_request_id' => $checkoutRequestID,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($queryUrl, $payload);

            $responseData = $response->json();

            // #region agent log
            @file_put_contents('c:\\Users\\LENOVO\\Downloads\\DEVELOPMENT\\IST-COLLEGE\\SAAS APP LARAVEL\\.cursor\\debug.log', json_encode(['timestamp' => round(microtime(true) * 1000), 'hypothesisId' => 'A', 'location' => 'MpesaStkService::querySTKPushStatus', 'message' => 'full_daraja_stk_query_response', 'data' => ['status' => $response->status(), 'response_keys' => array_keys($responseData ?? []), 'full_response' => $responseData]]) . "\n", FILE_APPEND);
            // #endregion

            if ($response->successful() && isset($responseData['ResponseCode'])) {
                if ($responseData['ResponseCode'] == '0') {
                    // Payment found
                    $resultCode = $responseData['ResultCode'] ?? null;
                    $resultDesc = $responseData['ResultDesc'] ?? '';

                    Log::info('M-Pesa STK Push query successful', [
                        'checkout_request_id' => $checkoutRequestID,
                        'result_code' => $resultCode,
                        'result_desc' => $resultDesc,
                    ]);

                    return [
                        'success' => true,
                        'result_code' => $resultCode,
                        'result_desc' => $resultDesc,
                        'response_code' => $responseData['ResponseCode'],
                        'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                        'checkout_request_id' => $responseData['CheckoutRequestID'] ?? $checkoutRequestID,
                        'customer_message' => $responseData['CustomerMessage'] ?? '',
                        'is_paid' => $resultCode == '0', // ResultCode 0 means payment successful
                    ];
                } else {
                    Log::error('M-Pesa STK Push query failed', [
                        'checkout_request_id' => $checkoutRequestID,
                        'response_code' => $responseData['ResponseCode'] ?? '',
                        'error_message' => $responseData['ErrorMessage'] ?? '',
                    ]);

                    return [
                        'success' => false,
                        'error' => $responseData['ErrorMessage'] ?? 'Query request failed',
                        'response_code' => $responseData['ResponseCode'] ?? '',
                    ];
                }
            }

            Log::error('M-Pesa STK Push query unexpected response', [
                'checkout_request_id' => $checkoutRequestID,
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => 'Unexpected response from M-Pesa API',
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push query exception', [
                'checkout_request_id' => $checkoutRequestID,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to query STK Push status: '.$e->getMessage(),
            ];
        }
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
