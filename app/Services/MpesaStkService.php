<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        return !empty($this->consumerKey) 
            && !empty($this->consumerSecret) 
            && !empty($this->passkey) 
            && !empty($this->shortcode);
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

        throw new \Exception('Failed to generate M-Pesa access token after ' . $retryAttempts . ' attempts');
    }

    /**
     * Generate password for STK push (Base64 encoded)
     */
    protected function generatePassword(): string
    {
        $timestamp = $this->getTimestamp();
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
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
        if (!$this->isConfigured()) {
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

        // For development/testing: Use KES 1.00 for STK push, but keep actual amount for display
        $actualAmount = $data['amount'];
        $stkPushAmount = config('app.env') === 'production' ? $actualAmount : 1.00;

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transactionType,
            'Amount' => (int) $stkPushAmount, // M-Pesa requires integer amount - use 1.00 for dev/testing
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $data['account_reference'] ?? 'INV-' . ($data['invoice_id'] ?? ''),
            'TransactionDesc' => $data['transaction_desc'] ?? 'Payment for Invoice ' . ($data['invoice_id'] ?? ''),
        ];

        // Log the amount difference in development
        if (config('app.env') !== 'production') {
            Log::info('M-Pesa STK Push: Using test amount', [
                'actual_amount' => $actualAmount,
                'stk_push_amount' => $stkPushAmount,
                'invoice_id' => $data['invoice_id'] ?? null,
            ]);
        }

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
                'error' => 'Unexpected response from M-Pesa API',
                'response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate STK Push: ' . $e->getMessage(),
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
            $phone = '254' . substr($phone, 1);
        }

        // If doesn't start with 254, add it
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Verify callback IP address (production only)
     */
    public function verifyCallbackIP(string $ip): bool
    {
        if (!config('mpesa.validate_callback_ip', false)) {
            return true; // Skip validation in sandbox/development
        }

        $whitelist = config('mpesa.callback_ip_whitelist', []);
        return in_array($ip, $whitelist);
    }
}

