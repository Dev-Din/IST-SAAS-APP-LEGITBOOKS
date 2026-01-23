<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentJsonController extends Controller
{
    /**
     * Fetch all M-Pesa payments as JSON
     */
    public function fetch(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $query = Payment::where('tenant_id', $tenant->id)
            ->where('payment_method', 'mpesa')
            ->with(['invoice', 'subscription', 'account']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('transaction_status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->has('receipt')) {
            $query->where('mpesa_receipt', 'like', '%'.$request->input('receipt').'%');
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        // Format payments for JSON
        $formattedPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'mpesa_receipt' => $payment->mpesa_receipt,
                'amount' => (float) $payment->amount,
                'phone' => $payment->phone,
                'transaction_status' => $payment->transaction_status,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'reference' => $payment->reference,
                'checkout_request_id' => $payment->checkout_request_id,
                'merchant_request_id' => $payment->merchant_request_id,
                'invoice' => $payment->invoice ? [
                    'id' => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'total' => (float) $payment->invoice->total,
                ] : null,
                'subscription' => $payment->subscription ? [
                    'id' => $payment->subscription->id,
                    'plan' => $payment->subscription->plan,
                    'status' => $payment->subscription->status,
                ] : null,
                'raw_callback' => $payment->raw_callback,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formattedPayments->count(),
            'payments' => $formattedPayments,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Store payments to JSON file
     */
    public function store(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $query = Payment::where('tenant_id', $tenant->id)
            ->where('payment_method', 'mpesa')
            ->with(['invoice', 'subscription', 'account']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('transaction_status', $request->input('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        // Format payments for JSON
        $formattedPayments = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'mpesa_receipt' => $payment->mpesa_receipt,
                'amount' => (float) $payment->amount,
                'phone' => $payment->phone,
                'transaction_status' => $payment->transaction_status,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $payment->updated_at->format('Y-m-d H:i:s'),
                'reference' => $payment->reference,
                'checkout_request_id' => $payment->checkout_request_id,
                'merchant_request_id' => $payment->merchant_request_id,
                'invoice' => $payment->invoice ? [
                    'id' => $payment->invoice->id,
                    'invoice_number' => $payment->invoice->invoice_number,
                    'total' => (float) $payment->invoice->total,
                    'status' => $payment->invoice->status,
                ] : null,
                'subscription' => $payment->subscription ? [
                    'id' => $payment->subscription->id,
                    'plan' => $payment->subscription->plan,
                    'status' => $payment->subscription->status,
                    'started_at' => $payment->subscription->started_at ? $payment->subscription->started_at->format('Y-m-d H:i:s') : null,
                    'ends_at' => $payment->subscription->ends_at ? $payment->subscription->ends_at->format('Y-m-d H:i:s') : null,
                ] : null,
                'raw_callback' => $payment->raw_callback,
            ];
        });

        $data = [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'exported_at' => now()->format('Y-m-d H:i:s'),
            'count' => $formattedPayments->count(),
            'payments' => $formattedPayments,
        ];

        // Generate filename
        $filename = 'mpesa_payments_'.$tenant->id.'_'.date('Y-m-d_His').'.json';
        $filepath = 'payments/'.$filename;

        // Store JSON file
        Storage::put($filepath, json_encode($data, JSON_PRETTY_PRINT));

        Log::info('M-Pesa payments exported to JSON', [
            'tenant_id' => $tenant->id,
            'filename' => $filename,
            'count' => $formattedPayments->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payments stored to JSON file successfully',
            'filename' => $filename,
            'filepath' => $filepath,
            'download_url' => route('tenant.payments.json.download', ['filename' => $filename]),
            'count' => $formattedPayments->count(),
        ]);
    }

    /**
     * Download stored JSON file
     */
    public function download($filename, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $filepath = 'payments/'.$filename;

        // Verify file exists and belongs to tenant
        if (! Storage::exists($filepath)) {
            abort(404, 'File not found');
        }

        // Basic security: check if filename contains tenant ID
        if (! str_contains($filename, '_'.$tenant->id.'_')) {
            abort(403, 'Unauthorized access to file');
        }

        return Storage::download($filepath, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * List all stored JSON files for tenant
     */
    public function listFiles(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $files = Storage::files('payments');
        $tenantFiles = [];

        foreach ($files as $file) {
            // Check if file belongs to this tenant
            if (str_contains($file, '_'.$tenant->id.'_')) {
                $filename = basename($file);
                $tenantFiles[] = [
                    'filename' => $filename,
                    'path' => $file,
                    'size' => Storage::size($file),
                    'created_at' => date('Y-m-d H:i:s', Storage::lastModified($file)),
                    'download_url' => route('tenant.payments.json.download', ['filename' => $filename]),
                ];
            }
        }

        // Sort by created_at descending
        usort($tenantFiles, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return response()->json([
            'success' => true,
            'files' => $tenantFiles,
            'count' => count($tenantFiles),
        ]);
    }
}
