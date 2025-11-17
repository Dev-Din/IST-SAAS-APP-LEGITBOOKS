<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Account;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function processCallback(array $payload): ?Payment
    {
        $tenant = $this->tenantContext->getTenant();
        
        if (!$tenant) {
            Log::error('M-Pesa callback: Tenant context not set');
            return null;
        }

        // Validate M-Pesa callback payload
        // This is a simplified version - in production, add proper validation
        $phone = $payload['PhoneNumber'] ?? null;
        $amount = $payload['TransAmount'] ?? null;
        $transactionId = $payload['TransID'] ?? null;
        $reference = $payload['BillRefNumber'] ?? null;

        if (!$phone || !$amount || !$transactionId) {
            Log::error('M-Pesa callback: Missing required fields', $payload);
            return null;
        }

        // Find or create M-Pesa account
        $mpesaAccount = Account::where('tenant_id', $tenant->id)
            ->where('type', 'mpesa')
            ->first();

        if (!$mpesaAccount) {
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
        $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(Payment::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);

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
            'TransID' => 'SIM-' . uniqid(),
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
}

