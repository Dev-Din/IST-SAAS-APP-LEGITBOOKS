<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Mail\PHPMailerService;
use App\Services\MpesaService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected MpesaService $mpesaService,
        protected PaymentService $paymentService,
        protected PHPMailerService $mailer
    ) {}

    /**
     * Handle M-Pesa webhook callback
     */
    public function callback(Request $request)
    {
        $payload = $request->all();

        Log::info('M-Pesa webhook received', ['payload' => $payload]);

        // Verify webhook signature (in production, add proper verification)
        // For now, we'll process the callback

        try {
            // Extract invoice reference from BillRefNumber
            $billRef = $payload['BillRefNumber'] ?? null;
            $transactionId = $payload['TransID'] ?? null;
            $amount = $payload['TransAmount'] ?? null;
            $phone = $payload['PhoneNumber'] ?? null;

            if (! $transactionId || ! $amount) {
                Log::error('M-Pesa webhook: Missing required fields', $payload);

                return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Missing required fields'], 400);
            }

            // Check if payment already processed (idempotency)
            $existingPayment = Payment::where('reference', $transactionId)->first();
            if ($existingPayment) {
                Log::info('M-Pesa webhook: Payment already processed', ['payment_id' => $existingPayment->id]);

                return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Payment already processed']);
            }

            // Find invoice by reference (if BillRefNumber contains invoice ID or token)
            $invoice = null;
            if ($billRef) {
                // Try to find by invoice number or ID
                $invoice = Invoice::where('invoice_number', $billRef)
                    ->orWhere('payment_token', $billRef)
                    ->first();
            }

            // Process payment via MpesaService
            $payment = $this->mpesaService->processCallback($payload);

            if (! $payment) {
                Log::error('M-Pesa webhook: Failed to process payment', $payload);

                return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Payment processing failed'], 500);
            }

            // Allocate payment to invoice if found
            if ($invoice) {
                $this->tenantContext->setTenant($invoice->tenant);

                DB::transaction(function () use ($payment, $invoice) {
                    $allocatedAmount = min($payment->amount, $invoice->getOutstandingAmount());

                    if ($allocatedAmount > 0) {
                        // Create allocation
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

                        // Process journaling
                        $this->paymentService->processPayment($payment, [
                            ['invoice_id' => $invoice->id, 'amount' => $allocatedAmount],
                        ]);

                        // Send receipt email
                        $this->sendReceiptEmail($invoice, $payment);
                    }
                });
            } else {
                // Process payment without invoice allocation (manual allocation later)
                $this->paymentService->processPayment($payment, []);
            }

            Log::info('M-Pesa webhook processed successfully', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Payment processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('M-Pesa webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Internal error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send payment receipt email
     */
    protected function sendReceiptEmail(Invoice $invoice, Payment $payment): void
    {
        $tenant = $invoice->tenant;
        $contact = $invoice->contact;

        if (! $contact->email) {
            return;
        }

        $html = view('emails.invoice.receipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'tenant' => $tenant,
            'contact' => $contact,
        ])->render();

        $this->mailer->send([
            'to' => $contact->email,
            'subject' => "Payment Received - Invoice {$invoice->invoice_number}",
            'html' => $html,
            'from_name' => $tenant->name,
        ]);
    }
}
