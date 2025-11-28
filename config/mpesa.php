<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Daraja API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Safaricom M-Pesa Daraja API integration
    |
    */

    'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // sandbox or production

    'consumer_key' => env('MPESA_CONSUMER_KEY', 'BPimwLlrxM2ezevmoGfjeB3jGdw4r4MAuNv9kI3zmsLgMrOX'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', 'ZYzqQAc65YTxmRIN8AZ0hyGUl1lVrGB9V9Zo9ZDF0JUMdr9bso3X0dEJ0otU9UXw'),
    
    'passkey' => env('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
    'shortcode' => env('MPESA_SHORTCODE', '174379'),
    
    'base_url' => env('MPESA_BASE_URL', 'https://sandbox.safaricom.co.ke'),
    
    'auth_url' => env('MPESA_AUTH_KEY', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'),
    'stk_push_url' => env('MPESA_EXPRESS_API', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'),
    
    'callback_base' => env('MPESA_CALLBACK_BASE', env('APP_URL')),
    'callback_url' => env('MPESA_CALLBACK_URL', env('MPESA_CALLBACK_BASE', env('APP_URL')) . '/api/payments/mpesa/callback'),
    
    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
    
    /*
    |--------------------------------------------------------------------------
    | Token Caching
    |--------------------------------------------------------------------------
    |
    | Cache the access token to avoid unnecessary API calls
    |
    */
    'token_cache_key' => 'mpesa_access_token',
    'token_cache_ttl' => 3600, // 1 hour in seconds
    
    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | IP whitelist for callback validation (production only)
    |
    */
    'callback_ip_whitelist' => [
        '196.201.214.200',
        '196.201.214.206',
        '196.201.213.114',
        '196.201.214.207',
        '196.201.214.208',
        '196.201.213.44',
        '196.201.212.127',
        '196.201.212.128',
        '196.201.212.129',
        '196.201.212.132',
        '196.201.212.136',
        '196.201.212.138',
    ],
    
    'validate_callback_ip' => env('MPESA_VALIDATE_CALLBACK_IP', false),
    
    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Retry settings for failed token fetch
    |
    */
    'token_retry_attempts' => 3,
    'token_retry_delay' => 1, // seconds
];

