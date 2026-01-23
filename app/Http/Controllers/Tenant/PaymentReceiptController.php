<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MpesaReceiptValidationService;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected MpesaReceiptValidationService $receiptService
    ) {}

    /**
     * Show payment receipts page - All M-Pesa confirmations
     */
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        // Show all M-Pesa payments (with or without receipts)
        $payments = Payment::where('tenant_id', $tenant->id)
            ->where('payment_method', 'mpesa')
            ->with(['invoice', 'subscription', 'account'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('tenant.payments.receipts', compact('payments'));
    }

    /**
     * Validate a specific payment receipt
     */
    public function validate(Request $request, $paymentId, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        if (! $payment->mpesa_receipt) {
            return response()->json([
                'success' => false,
                'error' => 'No M-Pesa receipt number found for this payment',
            ], 400);
        }

        $validation = $this->receiptService->validatePaymentReceipt($payment);

        return response()->json($validation);
    }

    /**
     * Fetch payment by receipt number
     */
    public function fetchByReceipt(Request $request, TenantContext $tenantContext)
    {
        $request->validate([
            'receipt_number' => 'required|string',
        ]);

        $receiptNumber = $request->input('receipt_number');
        $result = $this->receiptService->fetchPaymentByReceipt($receiptNumber);

        if (! $result) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found in database or M-Pesa system',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Validate all pending payments
     */
    public function validatePending(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $results = $this->receiptService->validatePendingPayments($tenant->id);

        return response()->json([
            'success' => true,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Show payment receipt details
     */
    public function show($paymentId, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $payment = Payment::where('id', $paymentId)
            ->where('tenant_id', $tenant->id)
            ->with(['invoice', 'subscription', 'account'])
            ->firstOrFail();

        // Validate receipt if it exists
        $validation = null;
        if ($payment->mpesa_receipt) {
            $validation = $this->receiptService->validatePaymentReceipt($payment);
        }

        return view('tenant.payments.receipt', compact('payment', 'validation'));
    }
}
