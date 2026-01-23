<?php

namespace App\Helpers;

class PaymentHelper
{
    /**
     * Get demo payment details for testing/development
     */
    public static function getDemoPaymentDetails(string $gateway): array
    {
        $demoDetails = [
            'mpesa' => [
                'phone_number' => '254712345678',
                'name' => 'Demo M-Pesa Account',
                'note' => 'Use any phone number for testing. Payment will be auto-approved in development mode.',
            ],
            'debit_card' => [
                'card_number' => '4111111111111111',
                'cardholder_name' => 'Demo User',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
                'note' => 'Use test card number 4111 1111 1111 1111. Payment will be auto-approved in development mode.',
            ],
            'credit_card' => [
                'card_number' => '5555555555554444',
                'cardholder_name' => 'Demo User',
                'expiry_month' => '12',
                'expiry_year' => '2025',
                'cvv' => '123',
                'note' => 'Use test card number 5555 5555 5555 4444. Payment will be auto-approved in development mode.',
            ],
            'paypal' => [
                'email' => 'demo@paypal.com',
                'password' => 'demo123',
                'note' => 'Use any email/password for testing. Payment will be auto-approved in development mode.',
            ],
        ];

        return $demoDetails[$gateway] ?? [];
    }

    /**
     * Check if we should use demo/test mode
     */
    public static function isTestMode(): bool
    {
        return config('app.env') === 'local' || config('app.debug') === true;
    }

    /**
     * Process demo payment (for testing only)
     */
    public static function processDemoPayment(string $gateway, array $paymentData): array
    {
        // Simulate payment processing delay
        usleep(500000); // 0.5 seconds

        return [
            'success' => true,
            'transaction_id' => 'DEMO-'.strtoupper($gateway).'-'.time(),
            'message' => 'Demo payment processed successfully',
            'is_demo' => true,
        ];
    }
}
