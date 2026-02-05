<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MpesaService;
use App\Services\MpesaStkService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected MpesaService $mpesaService,
        protected PaymentService $paymentService
    ) {}

    /**
     * Get MpesaStkService instance (lazy loading to avoid errors if not configured)
     */
    protected function getMpesaStkService(): MpesaStkService
    {
        return app(MpesaStkService::class);
    }

    /**
     * Resolve invoice for public pay route without tenant scope (so link works regardless of viewer context).
     * Validates payment_token and tenant; aborts 404 with clear message if invalid.
     */
    protected function findInvoiceForPayment($invoiceId, string $token): Invoice
    {
        $invoice = Invoice::withoutGlobalScope('tenant')->findOrFail($invoiceId);

        if (! $invoice->payment_token) {
            abort(404, 'This invoice has not been sent yet. Please contact the sender to send the invoice first.');
        }

        if ($invoice->payment_token !== $token) {
            abort(404, 'Invalid payment link. The payment token does not match.');
        }

        $invoice->load('tenant');
        if (! $invoice->tenant) {
            abort(404, 'Invoice tenant not found.');
        }

        return $invoice;
    }

    /**
     * Show payment page for invoice
     */
    public function show($invoiceId, string $token)
    {
        try {
            $invoice = $this->findInvoiceForPayment($invoiceId, $token);

            // Check if invoice is already paid
            if ($invoice->status === 'paid') {
                $invoice->load('contact', 'lineItems');

                return view('invoice.payment.paid', compact('invoice'));
            }

            $this->tenantContext->setTenant($invoice->tenant);

            $invoice->load('contact', 'lineItems');

            return view('invoice.payment.show', compact('invoice'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Invoice not found.');
        } catch (\Exception $e) {
            Log::error('Payment page error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'An error occurred while loading the payment page: '.$e->getMessage());
        }
    }

    /**
     * Show payment success page
     */
    public function success($invoiceId, string $token)
    {
        try {
            $invoice = $this->findInvoiceForPayment($invoiceId, $token);

            // Load relationships
            $invoice->load('contact', 'lineItems', 'paymentAllocations.payment');

            $this->tenantContext->setTenant($invoice->tenant);

            // Sync recent pending payments with Daraja (so Refresh / page load can show receipt after user has paid)
            $this->syncInvoicePendingPaymentsWithDaraja($invoice);

            // Reload invoice and relationships after sync
            $invoice->refresh();
            $invoice->load('contact', 'lineItems', 'paymentAllocations.payment');

            // Check for recent pending payments (for view if needed)
            $recentPayments = Payment::where('invoice_id', $invoice->id)
                ->where('transaction_status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->get();

            // Check payment status
            $outstanding = $invoice->getOutstandingAmount();
            $isPaid = $invoice->status === 'paid' || $outstanding <= 0;

            // If request wants JSON (for polling), return JSON response
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'isPaid' => $isPaid,
                    'status' => $invoice->status,
                    'outstanding' => $outstanding,
                    'total' => $invoice->total,
                    'invoice_number' => $invoice->invoice_number,
                ]);
            }

            // If paid after sync, redirect to receipt page for a clean URL
            if ($isPaid) {
                return redirect()->to("/pay/{$invoice->id}/{$invoice->payment_token}/receipt");
            }

            return view('invoice.payment.success', compact('invoice', 'isPaid', 'outstanding', 'recentPayments'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Invoice not found.');
        } catch (\Exception $e) {
            Log::error('Payment success page error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'An error occurred while loading the payment success page.');
        }
    }

    /**
     * Show public receipt page for a paid invoice (requires valid token).
     * Redirects to payment page if invoice is not yet paid.
     */
    public function receipt($invoiceId, string $token)
    {
        try {
            $invoice = $this->findInvoiceForPayment($invoiceId, $token);

            $invoice->load('contact', 'lineItems', 'paymentAllocations.payment');

            $this->tenantContext->setTenant($invoice->tenant);

            $outstanding = $invoice->getOutstandingAmount();
            $isPaid = $invoice->status === 'paid' || $outstanding <= 0;

            if (! $isPaid) {
                return redirect()->to("/pay/{$invoice->id}/{$invoice->payment_token}");
            }

            return view('invoice.payment.receipt', compact('invoice'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Invoice not found.');
        } catch (\Exception $e) {
            Log::error('Payment receipt page error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'An error occurred while loading the receipt.');
        }
    }

    /**
     * Show public failed payment page (requires valid token).
     * Displays retry message and link back to payment page.
     */
    public function failed($invoiceId, string $token)
    {
        try {
            $invoice = $this->findInvoiceForPayment($invoiceId, $token);

            $invoice->load('contact', 'lineItems');

            $this->tenantContext->setTenant($invoice->tenant);

            return view('invoice.payment.failed', compact('invoice'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Invoice not found.');
        } catch (\Exception $e) {
            Log::error('Payment failed page error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'An error occurred while loading the page.');
        }
    }

    /**
     * Process M-Pesa STK push payment
     */
    public function processMpesa(Request $request, $invoiceId, string $token)
    {
        $invoice = $this->findInvoiceForPayment($invoiceId, $token);

        $this->tenantContext->setTenant($invoice->tenant);

        $validated = $request->validate([
            'phone_number' => 'required|string|regex:/^254\d{9}$/',
        ]);

        try {
            $amount = $invoice->getOutstandingAmount();
            $phone = $validated['phone_number'];

            $existingPayment = Payment::where('invoice_id', $invoice->id)
                ->where('phone', $this->formatPhoneNumber($phone))
                ->where('transaction_status', 'pending')
                ->where('created_at', '>', now()->subMinutes(5))
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'error' => 'A payment request is already pending for this invoice. Please wait a few minutes.',
                ], 409);
            }

            $result = $this->getMpesaStkService()->initiateSTKPush([
                'invoice_id' => $invoice->id,
                'phone_number' => $phone,
                'amount' => $amount,
                'account_reference' => $invoice->invoice_number,
                'transaction_desc' => 'Payment for Invoice '.$invoice->invoice_number,
            ]);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to initiate STK push',
                ], 400);
            }

            $mpesaAccount = \App\Models\Account::where('tenant_id', $invoice->tenant_id)
                ->where('type', 'mpesa')
                ->first();

            if (! $mpesaAccount) {
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

            // Globally unique payment number: payment_number has a global unique constraint
            $tenantSeq = Payment::where('tenant_id', $invoice->tenant_id)->count() + 1;
            $paymentNumber = 'PAY-'.date('Ymd').'-'.$invoice->tenant_id.'-'.str_pad($tenantSeq, 4, '0', STR_PAD_LEFT);

            $payment = Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'payment_number' => $paymentNumber,
                'payment_date' => now()->toDateString(),
                'account_id' => $mpesaAccount->id ?? null,
                'contact_id' => $invoice->contact_id,
                'amount' => $amount,
                'payment_method' => 'mpesa',
                'phone' => $this->formatPhoneNumber($phone),
                'mpesa_receipt' => null,
                'transaction_status' => 'pending',
                'checkout_request_id' => $result['checkoutRequestID'],
                'merchant_request_id' => $result['merchantRequestID'],
                'raw_callback' => null,
            ]);

            Log::info('STK Push payment record created from invoice payment page', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'checkout_request_id' => $result['checkoutRequestID'],
            ]);

            return response()->json([
                'success' => true,
                'checkoutRequestID' => $result['checkoutRequestID'],
                'customerMessage' => $result['customerMessage'],
                'merchantRequestID' => $result['merchantRequestID'],
                'payment_id' => $payment->id,
                'message' => 'STK push initiated. Please check your phone to complete the payment.',
            ]);
        } catch (\Exception $e) {
            Log::error('M-Pesa payment processing failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'An error occurred while processing payment.'], 500);
        }
    }

    /**
     * Process card/PayPal payment (placeholder)
     */
    public function processCard(Request $request, $invoiceId, string $token)
    {
        $invoice = $this->findInvoiceForPayment($invoiceId, $token);

        $this->tenantContext->setTenant($invoice->tenant);

        // Placeholder for card/PayPal integration
        return response()->json([
            'message' => 'Card/PayPal payment integration coming soon.',
        ], 501);
    }

    /**
     * Check invoice payment status by checkout request ID (for frontend polling).
     * GET /pay/{invoice}/{token}/status?checkout_request_id=...
     * If payment is pending/failed in DB, queries Daraja STK Push Query API and updates DB.
     * Returns JSON: success, status (pending|success|failed), payment_id, invoice_paid, transaction, order.
     * Poll every 8–10 seconds; redirect to receipt when invoice_paid=true or status=success, to failed when status=failed.
     */
    public function checkPaymentStatus($invoiceId, string $token, Request $request)
    {
        $checkoutRequestId = $request->input('checkout_request_id');

        if (! $checkoutRequestId) {
            return response()->json([
                'status' => 'error',
                'error' => 'Checkout request ID is required',
            ], 400);
        }

        $invoice = $this->findInvoiceForPayment($invoiceId, $token);

        $this->tenantContext->setTenant($invoice->tenant);

        // Find payment by checkout_request_id and invoice_id (unscoped so tenant scope cannot hide the payment)
        $payment = Payment::withoutGlobalScope('tenant')
            ->where('checkout_request_id', $checkoutRequestId)
            ->where('invoice_id', $invoice->id)
            ->first();

        if ($payment && (int) $payment->tenant_id !== (int) $invoice->tenant_id) {
            $payment = null;
        }

        if (! $payment) {
            Log::warning('Payment not found for status check', [
                'invoice_id' => $invoice->id,
                'checkout_request_id_tail' => strlen($checkoutRequestId) >= 8 ? substr($checkoutRequestId, -8) : $checkoutRequestId,
                'tenant_id' => $invoice->tenant_id,
                'recent_payments_count' => Payment::withoutGlobalScope('tenant')
                    ->where('invoice_id', $invoice->id)
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->count(),
            ]);

            return response()->json([
                'status' => 'error',
                'error' => 'Payment not found',
            ], 404);
        }

        Log::info('Invoice payment status check: payment found', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);

        // If payment is still pending or failed (might be incorrectly marked), query Daraja API to get latest status
        if ($payment->transaction_status === 'pending' || $payment->transaction_status === 'failed') {
            $mpesaService = app(\App\Services\MpesaStkService::class);
            Log::info('Invoice payment status check: querying Daraja', ['payment_id' => $payment->id]);
            $queryResult = $mpesaService->querySTKPushStatus($checkoutRequestId);

            if ($queryResult['success'] && isset($queryResult['is_paid']) && $queryResult['is_paid']) {
                Log::info('Invoice payment status check: Daraja returned paid', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                ]);

                // Payment was successful - update payment status
                try {
                    DB::beginTransaction();

                    $payment->update([
                        'transaction_status' => 'completed',
                        'reference' => $queryResult['checkout_request_id'] ?? $checkoutRequestId,
                    ]);

                    // Allocate payment to invoice if not already allocated
                    $invoice->load('paymentAllocations');
                    $existingAllocation = $invoice->paymentAllocations()
                        ->where('payment_id', $payment->id)
                        ->first();

                    if (! $existingAllocation && $payment->invoice_id) {
                        $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());
                        if ($allocatedAmount > 0) {
                            $paymentService = app(\App\Services\PaymentService::class);
                            $paymentService->processPayment($payment, [
                                ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
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
                        }
                    }

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to process payment from status check', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($queryResult['success'] && isset($queryResult['result_code'])) {
                if ($queryResult['result_code'] === '4999') {
                    Log::info('Invoice payment status check: Daraja still processing (4999)', [
                        'payment_id' => $payment->id,
                    ]);

                    // Optional retry: wait 2s and query once more before returning
                    sleep(2);
                    $retryResult = $mpesaService->querySTKPushStatus($checkoutRequestId);
                    if ($retryResult['success'] && ! empty($retryResult['is_paid'])) {
                        Log::info('Invoice payment status check: Daraja returned paid on retry', [
                            'payment_id' => $payment->id,
                        ]);
                        try {
                            DB::beginTransaction();
                            $payment->update([
                                'transaction_status' => 'completed',
                                'reference' => $retryResult['checkout_request_id'] ?? $checkoutRequestId,
                            ]);
                            $invoice->load('paymentAllocations');
                            $existingAllocation = $invoice->paymentAllocations()
                                ->where('payment_id', $payment->id)
                                ->first();
                            if (! $existingAllocation && $payment->invoice_id) {
                                $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());
                                if ($allocatedAmount > 0) {
                                    app(\App\Services\PaymentService::class)->processPayment($payment, [
                                        ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                                    ]);
                                    $outstanding = $invoice->fresh()->getOutstandingAmount();
                                    if ($outstanding <= 0) {
                                        $invoice->update(['status' => 'paid', 'payment_status' => 'paid']);
                                    } elseif ($outstanding < $invoice->total) {
                                        $invoice->update(['payment_status' => 'partial']);
                                    }
                                }
                            }
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error('Failed to process payment from status check retry', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } elseif ($queryResult['result_code'] != '0') {
                    // Only mark as failed if result_code explicitly indicates failure (not 4999)
                    $payment->update([
                        'transaction_status' => 'failed',
                    ]);
                }
            } else {
                // querySTKPushStatus returned success === false (API error, not configured, etc.)
                // Do not mark payment as failed; log and keep returning pending
                Log::info('Invoice payment status check: Daraja query failed or not configured', [
                    'payment_id' => $payment->id,
                    'error' => $queryResult['error'] ?? 'unknown',
                ]);
            }

            // Refresh payment to get latest status
            $payment->refresh();
        }

        // Check invoice payment status
        $invoice->refresh();
        $outstanding = $invoice->getOutstandingAmount();
        $isPaid = $invoice->status === 'paid' || $outstanding <= 0;

        // Return status similar to subscription payment check (pending|success|failed)
        $status = match ($payment->transaction_status) {
            'completed' => $isPaid ? 'success' : 'processing',
            'failed', 'cancelled' => 'failed',
            default => 'pending',
        };

        // API-style response for polling: success, status, payment_id, transaction, order (invoice)
        $payment->refresh();
        return response()->json([
            'success' => true,
            'status' => $status,
            'payment_id' => (string) $payment->id,
            'payment_status' => $payment->transaction_status,
            'invoice_paid' => $isPaid,
            'outstanding' => $outstanding,
            'transaction' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'status' => $payment->transaction_status === 'completed' ? 'success' : ($payment->transaction_status === 'failed' ? 'failed' : 'pending'),
                'completed_at' => $payment->transaction_status === 'completed' && $payment->updated_at ? $payment->updated_at->toIso8601String() : null,
                'mpesa_receipt' => $payment->mpesa_receipt,
                'reference' => $payment->payment_number,
            ],
            'order' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total' => (float) $invoice->total,
            ],
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Sync recent pending payments for this invoice with Daraja STK Push Query.
     * When the user has already paid but callback was not received (e.g. tunnel mismatch),
     * loading the success page or clicking Refresh will run this and show the receipt.
     */
    protected function syncInvoicePendingPaymentsWithDaraja(Invoice $invoice): void
    {
        $pendingPayments = Payment::where('invoice_id', $invoice->id)
            ->where('transaction_status', 'pending')
            ->whereNotNull('checkout_request_id')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($pendingPayments->isEmpty()) {
            return;
        }

        $mpesaService = app(\App\Services\MpesaStkService::class);
        $paymentService = app(\App\Services\PaymentService::class);

        foreach ($pendingPayments as $payment) {
            $queryResult = $mpesaService->querySTKPushStatus($payment->checkout_request_id);

            if (! ($queryResult['success'] && ! empty($queryResult['is_paid']))) {
                continue;
            }

            try {
                DB::beginTransaction();

                $payment->update([
                    'transaction_status' => 'completed',
                    'reference' => $queryResult['checkout_request_id'] ?? $payment->checkout_request_id,
                ]);

                $invoice->load('paymentAllocations');
                $existingAllocation = $invoice->paymentAllocations()
                    ->where('payment_id', $payment->id)
                    ->first();

                if (! $existingAllocation && $payment->invoice_id) {
                    $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());
                    if ($allocatedAmount > 0) {
                        $paymentService->processPayment($payment, [
                            ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                        ]);

                        $outstanding = $invoice->fresh()->getOutstandingAmount();
                        if ($outstanding <= 0) {
                            $invoice->update(['status' => 'paid', 'payment_status' => 'paid']);
                        } elseif ($outstanding < $invoice->total) {
                            $invoice->update(['payment_status' => 'partial']);
                        }
                    }
                }

                DB::commit();
                Log::info('Invoice payment synced from Daraja on success page load', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                ]);

                return;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to sync payment from Daraja on success page', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Format phone number to 254XXXXXXXXX format
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
     * Allocate payment to invoice and update status
     */
    protected function allocatePaymentToInvoice($payment, Invoice $invoice): void
    {
        DB::transaction(function () use ($payment, $invoice) {
            $amount = min($payment->amount, $invoice->getOutstandingAmount());

            // Create payment allocation
            $invoice->paymentAllocations()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
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

            // Process payment journaling
            $this->paymentService->processPayment($payment, [
                ['invoice_id' => $invoice->id, 'amount' => $amount],
            ]);
        });
    }
}
