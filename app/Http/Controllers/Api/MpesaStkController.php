<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MpesaStkService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MpesaStkController extends Controller
{
    public function __construct(
        protected MpesaStkService $mpesaService,
        protected PaymentService $paymentService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Initiate STK Push payment
     * 
     * POST /api/payments/mpesa/stk-push
     */
    public function initiateSTKPush(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'phone_number' => 'required|string|regex:/^(\+?254|0)[0-9]{9}$/',
            'amount' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($request->invoice_id);
            
            // Set tenant context
            $this->tenantContext->setTenant($invoice->tenant);
            
            // Load invoice relationships
            $invoice->load('tenant', 'contact');

            // Use outstanding amount if amount not provided
            $amount = $request->amount ?? $invoice->getOutstandingAmount();

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invoice is already paid in full',
                ], 400);
            }

            // Check for duplicate request (idempotency)
            $existingPayment = Payment::where('invoice_id', $invoice->id)
                ->where('phone', $this->formatPhoneNumber($request->phone_number))
                ->where('transaction_status', 'pending')
                ->where('created_at', '>', now()->subMinutes(5))
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'error' => 'A payment request is already pending for this invoice. Please wait a few minutes.',
                ], 409);
            }

            // Initiate STK Push
            $stkResult = $this->mpesaService->initiateSTKPush([
                'invoice_id' => $invoice->id,
                'phone_number' => $request->phone_number,
                'amount' => $amount,
                'account_reference' => $invoice->invoice_number,
                'transaction_desc' => 'Payment for Invoice ' . $invoice->invoice_number,
            ]);

            if (!$stkResult['success']) {
                return response()->json($stkResult, 400);
            }

            // Get or create M-Pesa account
            $mpesaAccount = \App\Models\Account::where('tenant_id', $invoice->tenant_id)
                ->where('type', 'mpesa')
                ->first();

            if (!$mpesaAccount) {
                // Create M-Pesa account if it doesn't exist
                $cashAccount = \App\Models\ChartOfAccount::where('tenant_id', $invoice->tenant_id)
                    ->where('code', '1400')
                    ->first();

                if ($cashAccount) {
                    $mpesaAccount = \App\Models\Account::create([
                        'tenant_id' => $invoice->tenant_id,
                        'name' => 'M-Pesa',
                        'type' => 'mpesa',
                        'chart_of_account_id' => $cashAccount->id,
                        'is_active' => true,
                    ]);
                }
            }

            // Generate payment number
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . str_pad(
                Payment::where('tenant_id', $invoice->tenant_id)->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Save payment record with actual amount (not the test amount used for STK push)
            // The STK push will charge 1.00 in development, but we store the actual amount
            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'payment_number' => $paymentNumber,
                'payment_date' => now()->toDateString(),
                'account_id' => $mpesaAccount->id ?? null,
                'contact_id' => $invoice->contact_id,
                'amount' => $amount, // Store actual amount, not the test 1.00
                'payment_method' => 'mpesa',
                'phone' => $this->formatPhoneNumber($request->phone_number),
                'mpesa_receipt' => null, // Will be updated on callback
                'transaction_status' => 'pending',
                'checkout_request_id' => $stkResult['checkoutRequestID'],
                'merchant_request_id' => $stkResult['merchantRequestID'],
                'raw_callback' => null,
            ]);

            Log::info('STK Push payment record created', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'checkout_request_id' => $stkResult['checkoutRequestID'],
            ]);

            return response()->json([
                'success' => true,
                'checkoutRequestID' => $stkResult['checkoutRequestID'],
                'customerMessage' => $stkResult['customerMessage'],
                'merchantRequestID' => $stkResult['merchantRequestID'],
                'payment_id' => $payment->id,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invoice not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('STK Push initiation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle M-Pesa callback
     * 
     * POST /api/payments/mpesa/callback
     */
    public function callback(Request $request)
    {
        // Verify callback IP (production only)
        $clientIP = $request->ip();
        if (!$this->mpesaService->verifyCallbackIP($clientIP)) {
            // In development, log but don't block (tunnel IPs are dynamic)
            if (config('app.env') !== 'production') {
                Log::info('M-Pesa callback from tunnel IP (development mode)', [
                    'ip' => $clientIP,
                    'x-forwarded-for' => $request->header('x-forwarded-for'),
                ]);
            } else {
                Log::warning('M-Pesa callback from unauthorized IP', [
                    'ip' => $clientIP,
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Log Cloudflare headers for debugging
        $cfHeaders = [
            'cf-ray' => $request->header('CF-RAY'),
            'cf-mitigated' => $request->header('cf-mitigated'),
            'cf-connecting-ip' => $request->header('CF-Connecting-IP'),
        ];
        
        // Log raw callback
        $rawCallback = $request->all();
        Log::info('M-Pesa callback received', [
            'ip' => $clientIP,
            'cf_headers' => $cfHeaders,
            'payload' => $rawCallback,
        ]);

        try {
            // Check for Cloudflare challenge HTML in payload
            $rawBody = $request->getContent();
            if (is_string($rawBody) && (strpos($rawBody, '<!DOCTYPE') !== false || strpos($rawBody, 'cf-challenge') !== false || strpos($rawBody, 'cloudflare') !== false)) {
                Log::error('M-Pesa callback blocked by Cloudflare challenge', [
                    'ip' => $clientIP,
                    'cf_headers' => $cfHeaders,
                    'body_preview' => substr($rawBody, 0, 500),
                ]);
                // Return 200 quickly to avoid retries
                return response()->json(['error' => 'Cloudflare challenge detected'], 200);
            }

            // Validate callback structure
            $body = $request->json()->all();
            
            if (!isset($body['Body'])) {
                Log::error('Invalid M-Pesa callback structure', [
                    'payload' => $rawCallback,
                    'cf_headers' => $cfHeaders,
                ]);
                return response()->json(['error' => 'Invalid callback structure'], 400);
            }

            $stkCallback = $body['Body']['stkCallback'] ?? null;
            
            if (!$stkCallback) {
                Log::error('Missing stkCallback in M-Pesa callback', ['payload' => $rawCallback]);
                return response()->json(['error' => 'Missing stkCallback'], 400);
            }

            $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? null;
            $resultCode = $stkCallback['ResultCode'] ?? null;
            $resultDesc = $stkCallback['ResultDesc'] ?? '';

            // Find payment by checkout request ID
            $payment = Payment::with('tenant', 'invoice', 'subscription')->where('checkout_request_id', $checkoutRequestID)->first();

            // If not found by checkout_request_id, try merchant_request_id
            if (!$payment && isset($stkCallback['MerchantRequestID'])) {
                $payment = Payment::with('tenant', 'invoice', 'subscription')
                    ->where('merchant_request_id', $stkCallback['MerchantRequestID'])
                    ->first();
            }

            if (!$payment) {
                Log::warning('M-Pesa callback for unknown payment', [
                    'checkout_request_id' => $checkoutRequestID,
                    'merchant_request_id' => $stkCallback['MerchantRequestID'] ?? null,
                    'cf_headers' => $cfHeaders,
                ]);
                // Return 200 to avoid retries for unknown payments
                return response()->json(['error' => 'Payment not found'], 200);
            }

            // Set tenant context
            if ($payment->tenant) {
                $this->tenantContext->setTenant($payment->tenant);
            }

            // Check idempotency (already processed)
            if ($payment->transaction_status !== 'pending') {
                Log::info('M-Pesa callback already processed', [
                    'payment_id' => $payment->id,
                    'status' => $payment->transaction_status,
                ]);
                return response()->json(['message' => 'Callback already processed'], 200);
            }

            // Update payment with raw callback
            $payment->update([
                'raw_callback' => $rawCallback,
            ]);

            // Process based on result code
            if ($resultCode == 0) {
                // Success - extract payment details
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
                
                $mpesaReceipt = null;
                $callbackAmount = null; // Amount from M-Pesa callback (will be 1.00 in dev)
                $phoneNumber = null;
                $transactionDate = null;

                foreach ($callbackMetadata as $item) {
                    switch ($item['Name'] ?? '') {
                        case 'MpesaReceiptNumber':
                            $mpesaReceipt = $item['Value'] ?? null;
                            break;
                        case 'Amount':
                            $callbackAmount = $item['Value'] ?? null; // Amount from M-Pesa (1.00 in dev)
                            break;
                        case 'PhoneNumber':
                            $phoneNumber = $item['Value'] ?? null;
                            break;
                        case 'TransactionDate':
                            $transactionDate = $item['Value'] ?? null;
                            break;
                    }
                }

                // In development, use the actual amount from payment record (not the 1.00 from callback)
                // In production, use the callback amount
                $actualAmount = config('app.env') === 'production' 
                    ? ($callbackAmount ?? $payment->amount) 
                    : $payment->amount; // Keep the original amount in dev

                DB::transaction(function () use ($payment, $mpesaReceipt, $actualAmount, $phoneNumber, $transactionDate) {
                    // Ensure payment has account_id (M-Pesa account)
                    if (!$payment->account_id) {
                        $tenant = $payment->tenant;
                        $mpesaAccount = \App\Models\Account::where('tenant_id', $tenant->id)
                            ->where('type', 'mpesa')
                            ->first();

                        if (!$mpesaAccount) {
                            // Create M-Pesa account if it doesn't exist
                            $cashAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                                ->where('code', '1400')
                                ->first();

                            if ($cashAccount) {
                                $mpesaAccount = \App\Models\Account::create([
                                    'tenant_id' => $tenant->id,
                                    'name' => 'M-Pesa',
                                    'type' => 'mpesa',
                                    'chart_of_account_id' => $cashAccount->id,
                                    'is_active' => true,
                                ]);
                            }
                        }

                        if ($mpesaAccount) {
                            $payment->account_id = $mpesaAccount->id;
                        }
                    }

                    // Update payment
                    // In development: keep the actual amount (not the 1.00 from callback)
                    // In production: use the callback amount
                    $payment->update([
                        'mpesa_receipt' => $mpesaReceipt,
                        'transaction_status' => 'completed',
                        'amount' => $actualAmount, // Use actual amount (not callback amount in dev)
                        'phone' => $phoneNumber ?? $payment->phone,
                        'payment_method' => 'mpesa',
                        'reference' => $mpesaReceipt,
                        'payment_date' => $transactionDate ? date('Y-m-d', strtotime($transactionDate)) : now()->toDateString(),
                    ]);

                    // Handle subscription payment
                    if ($payment->subscription_id) {
                        $subscription = $payment->subscription;
                        
                        // Store before state for audit log
                        $subscriptionBefore = $subscription->getAttributes();
                        
                        // Activate subscription immediately
                        $subscription->update([
                            'status' => 'active',
                            'started_at' => now(),
                            'ends_at' => now()->addMonth(), // 1 month subscription
                            'next_billing_at' => now()->addMonth(),
                        ]);

                        // Create audit log for subscription activation
                        \App\Models\AuditLog::create([
                            'tenant_id' => $payment->tenant_id,
                            'model_type' => get_class($subscription),
                            'model_id' => $subscription->id,
                            'performed_by' => null, // System action
                            'action' => 'subscription_activated',
                            'before' => $subscriptionBefore,
                            'after' => $subscription->fresh()->getAttributes(),
                        ]);

                        Log::info('Subscription activated via M-Pesa payment', [
                            'subscription_id' => $subscription->id,
                            'payment_id' => $payment->id,
                            'plan' => $subscription->plan,
                            'cf_headers' => $cfHeaders,
                        ]);
                        
                        // Refresh subscription to ensure latest status
                        $subscription->refresh();

                        // Create journal entries for subscription payment (Debit Bank, Credit Revenue)
                        try {
                            $this->createSubscriptionJournalEntry($payment, $subscription);
                        } catch (\Exception $e) {
                            // Log but don't fail the payment processing
                            Log::error('Failed to create subscription journal entry', [
                                'payment_id' => $payment->id,
                                'subscription_id' => $subscription->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Create audit log for payment
                    \App\Models\AuditLog::create([
                        'tenant_id' => $payment->tenant_id,
                        'model_type' => get_class($payment),
                        'model_id' => $payment->id,
                        'performed_by' => null, // System action
                        'action' => 'payment_completed',
                        'before' => ['transaction_status' => 'pending'],
                        'after' => [
                            'transaction_status' => 'completed',
                            'mpesa_receipt' => $mpesaReceipt,
                            'amount' => $actualAmount,
                        ],
                    ]);

                    // Allocate payment to invoice (only for invoice payments, not subscription payments)
                    if ($payment->invoice_id && !$payment->subscription_id) {
                        $invoice = $payment->invoice;
                        $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());

                        if ($allocatedAmount > 0) {
                            // Create payment allocation
                            $invoice->paymentAllocations()->create([
                                'payment_id' => $payment->id,
                                'amount' => $allocatedAmount,
                            ]);

                            // Update invoice status
                            $outstanding = $invoice->fresh()->getOutstandingAmount();
                            if ($outstanding <= 0) {
                                $invoice->update([
                                    'status' => 'paid',
                                    'payment_status' => 'paid',
                                ]);
                            } elseif ($outstanding < $invoice->total) {
                                $invoice->update([
                                    'payment_status' => 'partial',
                                ]);
                            }

                            // Process journaling for invoice payments
                            $this->paymentService->processPayment($payment, [
                                ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                            ]);
                        }
                    }
                    
                    // Subscription payments journal entries are created above in the subscription block
                });

                Log::info('M-Pesa payment processed successfully', [
                    'payment_id' => $payment->id,
                    'mpesa_receipt' => $mpesaReceipt,
                    'invoice_id' => $payment->invoice_id,
                ]);

                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Payment processed successfully',
                ], 200);
            } else {
                // Failed
                $payment->update([
                    'transaction_status' => 'failed',
                ]);

                Log::warning('M-Pesa payment failed', [
                    'payment_id' => $payment->id,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                ]);

                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Callback received',
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('M-Pesa callback processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $rawCallback,
            ]);

            return response()->json([
                'error' => 'Callback processing failed',
            ], 500);
        }
    }

    /**
     * Create journal entry for subscription payment
     * Debit: Bank/Cash (M-Pesa), Credit: Revenue
     */
    protected function createSubscriptionJournalEntry(Payment $payment, $subscription): void
    {
        $tenant = $payment->tenant;
        
        // Get M-Pesa account (bank/cash)
        $mpesaAccount = $payment->account;
        if (!$mpesaAccount) {
            throw new \Exception('M-Pesa account not found for payment');
        }
        $mpesaCoa = $mpesaAccount->chartOfAccount;

        // Get Revenue account (typically code 4100)
        $revenueAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
            ->where('code', '4100')
            ->first();

        if (!$revenueAccount) {
            // Try alternative revenue codes
            $revenueAccount = \App\Models\ChartOfAccount::where('tenant_id', $tenant->id)
                ->whereIn('code', ['4000', '4100', '4200'])
                ->where('type', 'revenue')
                ->first();
        }

        if (!$revenueAccount) {
            throw new \Exception('Revenue account not found for subscription payment');
        }

        // Create journal entry
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad(\App\Models\JournalEntry::where('tenant_id', $tenant->id)->count() + 1, 4, '0', STR_PAD_LEFT);
        
        $journalEntry = \App\Models\JournalEntry::create([
            'tenant_id' => $tenant->id,
            'entry_number' => $entryNumber,
            'entry_date' => $payment->payment_date ?? now()->toDateString(),
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
            'description' => "Subscription payment for {$subscription->plan} plan",
            'is_posted' => true,
        ]);

        // Debit Bank/Cash (M-Pesa)
        \App\Models\JournalLine::create([
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $mpesaCoa->id,
            'type' => 'debit',
            'amount' => $payment->amount,
            'description' => "Subscription payment received",
        ]);

        // Credit Revenue
        \App\Models\JournalLine::create([
            'journal_entry_id' => $journalEntry->id,
            'chart_of_account_id' => $revenueAccount->id,
            'type' => 'credit',
            'amount' => $payment->amount,
            'description' => "Subscription revenue - {$subscription->plan} plan",
        ]);

        // Verify balance
        $journalEntry->calculateTotals();
        $journalEntry->save();

        if (!$journalEntry->isBalanced()) {
            throw new \Exception('Subscription journal entry is not balanced');
        }

        Log::info('Subscription journal entry created', [
            'journal_entry_id' => $journalEntry->id,
            'payment_id' => $payment->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Format phone number
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }
        
        return $phone;
    }
}

