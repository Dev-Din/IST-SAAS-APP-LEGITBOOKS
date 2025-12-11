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
     * Show payment page for invoice
     */
    public function show($invoiceId, string $token)
    {
        try {
            // Find invoice by ID
            $invoice = Invoice::findOrFail($invoiceId);

            // If invoice has no payment token, it hasn't been sent yet
            if (!$invoice->payment_token) {
                abort(404, 'This invoice has not been sent yet. Please contact the sender to send the invoice first.');
            }

            // Validate token
            if ($invoice->payment_token !== $token) {
                abort(404, 'Invalid payment link. The payment token does not match.');
            }

            // Check if invoice is already paid
            if ($invoice->status === 'paid') {
                $invoice->load('contact', 'lineItems', 'tenant');
                return view('invoice.payment.paid', compact('invoice'));
            }

            // Load tenant relationship first
            $invoice->load('tenant');
            
            // Set tenant context
            if (!$invoice->tenant) {
                abort(404, 'Invoice tenant not found.');
            }
            
            $this->tenantContext->setTenant($invoice->tenant);

            $invoice->load('contact', 'lineItems');

            return view('invoice.payment.show', compact('invoice'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-throw HTTP exceptions (404, 500, etc.) so they're handled properly
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Invoice not found.');
        } catch (\Exception $e) {
            Log::error('Payment page error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'An error occurred while loading the payment page: ' . $e->getMessage());
        }
    }

    /**
     * Show payment success page
     */
    public function success($invoiceId, string $token)
    {
        try {
            // Find invoice by ID
            $invoice = Invoice::findOrFail($invoiceId);

            // Validate token
            if ($invoice->payment_token !== $token) {
                abort(404, 'Invalid payment link. The payment token does not match.');
            }

            // Load relationships
            $invoice->load('contact', 'lineItems', 'tenant', 'paymentAllocations.payment');

            // Set tenant context
            if ($invoice->tenant) {
                $this->tenantContext->setTenant($invoice->tenant);
            }

            // Check for recent pending payments
            $recentPayments = Payment::where('invoice_id', $invoice->id)
                ->where('transaction_status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderBy('created_at', 'desc')
                ->get();

            // Check payment status
            $outstanding = $invoice->getOutstandingAmount();
            $isPaid = $invoice->status === 'paid' || $outstanding <= 0;

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
     * Process M-Pesa STK push payment
     */
    public function processMpesa(Request $request, $invoiceId, string $token)
    {
        // Find invoice by ID
        $invoice = Invoice::findOrFail($invoiceId);

        // Validate token
        if ($invoice->payment_token !== $token) {
            return response()->json(['error' => 'Invalid payment link.'], 404);
        }

        // Validate request
        $validated = $request->validate([
            'phone_number' => 'required|string|regex:/^254\d{9}$/',
        ]);

        // Set tenant context
        $this->tenantContext->setTenant($invoice->tenant);

        try {
            // Initiate STK push using the new service
            $amount = $invoice->getOutstandingAmount();
            $phone = $validated['phone_number'];

            // Check for duplicate request (idempotency)
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

            // Use the new MpesaStkService for real STK push
            $result = $this->getMpesaStkService()->initiateSTKPush([
                'invoice_id' => $invoice->id,
                'phone_number' => $phone,
                'amount' => $amount,
                'account_reference' => $invoice->invoice_number,
                'transaction_desc' => 'Payment for Invoice ' . $invoice->invoice_number,
            ]);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to initiate STK push',
                ], 400);
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

            // Create payment record with invoice_id set
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
                'mpesa_receipt' => null, // Will be updated on callback
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
        // Find invoice by ID
        $invoice = Invoice::findOrFail($invoiceId);

        // Validate token
        if ($invoice->payment_token !== $token) {
            return response()->json(['error' => 'Invalid payment link.'], 404);
        }

        // Set tenant context
        $this->tenantContext->setTenant($invoice->tenant);

        // Placeholder for card/PayPal integration
        return response()->json([
            'message' => 'Card/PayPal payment integration coming soon.',
        ], 501);
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
            $phone = '254' . substr($phone, 1);
        }

        // If doesn't start with 254, add it
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
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

