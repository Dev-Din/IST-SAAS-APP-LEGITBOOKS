<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMpesaCallbackJob;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MpesaService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(
        protected MpesaService $mpesaService,
        protected PaymentService $paymentService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle M-Pesa STK callback
     * POST /api/payments/mpesa/callback
     */
    public function callback(Request $request)
    {
        // Log Cloudflare headers for debugging
        $cfHeaders = $this->mpesaService->logCloudflareHeaders($request);

        // Get raw callback body
        $rawBody = $request->getContent();
        $rawCallback = $request->all();

        // Log raw callback
        Log::info('M-Pesa callback received', [
            'ip' => $request->ip(),
            'cf_headers' => $cfHeaders,
            'raw_body_preview' => substr($rawBody, 0, 500),
            'payload' => $rawCallback,
        ]);

        // Detect Cloudflare HTML challenge
        if (is_string($rawBody) && (
            strpos($rawBody, '<!DOCTYPE') !== false ||
            strpos($rawBody, 'cf-challenge') !== false ||
            strpos($rawBody, 'cloudflare') !== false ||
            strpos($rawBody, '<html') !== false
        )) {
            Log::error('M-Pesa callback blocked by Cloudflare challenge', [
                'ip' => $request->ip(),
                'cf_headers' => $cfHeaders,
                'body_preview' => substr($rawBody, 0, 1000),
            ]);

            // Return 200 quickly to avoid retries
            return response()->json(['error' => 'Cloudflare challenge detected'], 200);
        }

        try {
            // Verify callback IP (production only)
            $clientIP = $request->ip();
            if (! $this->mpesaService->verifyCallbackIP($clientIP)) {
                if (config('app.env') !== 'production') {
                    Log::info('M-Pesa callback from tunnel IP (development mode)', [
                        'ip' => $clientIP,
                        'cf_headers' => $cfHeaders,
                    ]);
                } else {
                    Log::warning('M-Pesa callback from unauthorized IP', [
                        'ip' => $clientIP,
                        'cf_headers' => $cfHeaders,
                    ]);

                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }

            // Parse callback
            $parsed = $this->mpesaService->parseCallback($rawCallback);

            if (! $parsed['valid']) {
                Log::error('Invalid M-Pesa callback structure', [
                    'error' => $parsed['error'] ?? 'Unknown error',
                    'payload' => $rawCallback,
                    'cf_headers' => $cfHeaders,
                ]);

                return response()->json(['error' => $parsed['error'] ?? 'Invalid callback'], 400);
            }

            $checkoutRequestID = $parsed['checkout_request_id'];
            $merchantRequestID = $parsed['merchant_request_id'];
            $resultCode = $parsed['result_code'];
            $resultDesc = $parsed['result_desc'];

            // Find payment by checkout_request_id (primary lookup). Use withoutGlobalScope so
            // invoice payments are always found regardless of tenant context (callback has no tenant).
            $payment = Payment::withoutGlobalScope('tenant')
                ->with(['tenant', 'subscription', 'user'])
                ->where('checkout_request_id', $checkoutRequestID)
                ->first();

            // Fallback 1: Try merchant_request_id
            if (! $payment && $merchantRequestID) {
                $payment = Payment::withoutGlobalScope('tenant')
                    ->with(['tenant', 'subscription', 'user'])
                    ->where('merchant_request_id', $merchantRequestID)
                    ->first();
            }

            // Fallback 2: Try phone + amount match (for robustness)
            if (! $payment && isset($parsed['phone']) && isset($parsed['amount'])) {
                $phone = $this->normalizePhone($parsed['phone']);
                $amount = (float) $parsed['amount'];

                // Find pending payment with matching phone and amount (within 5 minutes)
                $payment = Payment::withoutGlobalScope('tenant')
                    ->with(['tenant', 'subscription', 'user'])
                    ->where('phone', $phone)
                    ->where('amount', $amount)
                    ->where('transaction_status', 'pending')
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($payment) {
                    Log::info('M-Pesa callback matched by phone+amount fallback', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutRequestID,
                        'phone' => $phone,
                        'amount' => $amount,
                        'cf_headers' => $cfHeaders,
                    ]);
                }
            }

            if (! $payment) {
                Log::warning('M-Pesa callback for unknown payment', [
                    'checkout_request_id' => $checkoutRequestID,
                    'merchant_request_id' => $merchantRequestID,
                    'phone' => $parsed['phone'] ?? null,
                    'amount' => $parsed['amount'] ?? null,
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
                    'cf_headers' => $cfHeaders,
                ]);

                return response()->json(['message' => 'Callback already processed'], 200);
            }

            // Store raw callback
            $payment->update([
                'raw_callback' => $rawCallback,
            ]);

            // Process based on result code
            if ($resultCode == 0 && $parsed['success']) {
                // Success - extract payment details
                $mpesaReceipt = $parsed['mpesa_receipt'] ?? null;
                $callbackAmount = $parsed['amount'] ?? null;
                $phoneNumber = $parsed['phone'] ?? null;
                $transactionDate = $parsed['transaction_date'] ?? null;

                // In development, use the actual amount from payment record (not the 1.00 from callback)
                // In production, use the callback amount
                $actualAmount = config('app.env') === 'production'
                    ? ($callbackAmount ?? $payment->amount)
                    : $payment->amount; // Keep the original amount in dev

                DB::transaction(function () use ($payment, $mpesaReceipt, $actualAmount, $phoneNumber, $transactionDate, $cfHeaders) {
                    // Ensure payment has account_id (M-Pesa account)
                    if (! $payment->account_id) {
                        $tenant = $payment->tenant;
                        $mpesaAccount = \App\Models\Account::where('tenant_id', $tenant->id)
                            ->where('type', 'mpesa')
                            ->first();

                        if (! $mpesaAccount) {
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

                    // Store before state for audit log
                    $paymentBefore = $payment->getAttributes();

                    // Update payment
                    $payment->update([
                        'mpesa_receipt' => $mpesaReceipt,
                        'transaction_status' => 'completed',
                        'amount' => $actualAmount, // Use actual amount (not callback amount in dev)
                        'phone' => $phoneNumber ?? $payment->phone,
                        'payment_method' => 'mpesa',
                        'reference' => $mpesaReceipt,
                        'payment_date' => $transactionDate ? date('Y-m-d', strtotime($transactionDate)) : now()->toDateString(),
                        'currency' => $payment->currency ?? 'KES',
                    ]);

                    // Handle subscription payment
                    if ($payment->subscription_id) {
                        $subscription = $payment->subscription;

                        if ($subscription) {
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
                            AuditLog::create([
                                'tenant_id' => $payment->tenant_id,
                                'model_type' => get_class($subscription),
                                'model_id' => $subscription->id,
                                'performed_by' => $payment->user_id, // User who initiated payment
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
                        }
                    }

                    // Allocate payment to invoice (only for invoice payments, not subscription payments)
                    $invoice = null;
                    if ($payment->invoice_id && ! $payment->subscription_id) {
                        $invoice = $payment->invoice;
                    } elseif (! $payment->subscription_id) {
                        // Fallback: Try to find invoice by phone + amount + recent timestamp
                        // This handles cases where payment was created without invoice_id
                        $invoice = Invoice::where('tenant_id', $payment->tenant_id)
                            ->where('contact_id', $payment->contact_id)
                            ->where('status', '!=', 'paid')
                            ->where('created_at', '>=', now()->subDays(7)) // Within last 7 days
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($invoice) {
                            // Update payment with invoice_id for future reference
                            $payment->invoice_id = $invoice->id;
                            $payment->save();

                            Log::info('Payment linked to invoice via fallback', [
                                'payment_id' => $payment->id,
                                'invoice_id' => $invoice->id,
                                'cf_headers' => $cfHeaders,
                            ]);
                        }
                    }

                    if ($invoice && ! $payment->subscription_id) {
                        // Check if allocation already exists (idempotency)
                        $existingAllocation = $invoice->paymentAllocations()
                            ->where('payment_id', $payment->id)
                            ->first();

                        if (! $existingAllocation) {
                            $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());

                            if ($allocatedAmount > 0) {
                                // Process payment with allocation (PaymentService will create the allocation and journal entries)
                                $this->paymentService->processPayment($payment, [
                                    ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                                ]);

                                // Update invoice status after allocation
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

                                Log::info('Payment allocated to invoice', [
                                    'payment_id' => $payment->id,
                                    'invoice_id' => $invoice->id,
                                    'allocated_amount' => $allocatedAmount,
                                    'invoice_status' => $invoice->fresh()->status,
                                    'cf_headers' => $cfHeaders,
                                ]);
                            }
                        } else {
                            // Allocation already exists, just update invoice status
                            $outstanding = $invoice->fresh()->getOutstandingAmount();
                            if ($outstanding <= 0 && $invoice->status !== 'paid') {
                                $invoice->update([
                                    'status' => 'paid',
                                    'payment_status' => 'paid',
                                ]);
                            } elseif ($outstanding < $invoice->total && $invoice->payment_status !== 'partial') {
                                $invoice->update([
                                    'payment_status' => 'partial',
                                ]);
                            }
                        }
                    }

                    // Create audit log for payment
                    AuditLog::create([
                        'tenant_id' => $payment->tenant_id,
                        'model_type' => get_class($payment),
                        'model_id' => $payment->id,
                        'performed_by' => $payment->user_id, // User who initiated payment
                        'action' => 'payment_completed',
                        'before' => $paymentBefore,
                        'after' => $payment->fresh()->getAttributes(),
                    ]);
                });

                Log::info('M-Pesa payment processed successfully', [
                    'payment_id' => $payment->id,
                    'mpesa_receipt' => $mpesaReceipt,
                    'subscription_id' => $payment->subscription_id,
                    'cf_headers' => $cfHeaders,
                ]);

                // Enqueue job for audit/notification (requirements: enqueues ProcessMpesaCallbackJob)
                ProcessMpesaCallbackJob::dispatch($rawCallback, $payment->tenant_id);

                // Return 200 quickly (heavy work done in transaction)
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
                    'cf_headers' => $cfHeaders,
                ]);

                // Return 200 quickly
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
                'cf_headers' => $cfHeaders,
            ]);

            // Return 200 to avoid retries on processing errors
            return response()->json([
                'error' => 'Callback processing failed',
            ], 200);
        }
    }

    /**
     * Normalize phone number to 254XXXXXXXXX format
     */
    protected function normalizePhone(string $phone): string
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
}
