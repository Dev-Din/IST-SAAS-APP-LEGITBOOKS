<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TenantInvoiceAdminController extends Controller
{
    /**
     * Check if admin has permission
     */
    protected function ensurePermission(): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || (!$admin->hasRole('owner') && !$admin->hasPermission('tenants.view'))) {
            abort(403, 'You do not have permission to view tenant invoices.');
        }
    }

    /**
     * Get invoices list with filters
     */
    public function index(Request $request, Tenant $tenant)
    {
        $this->ensurePermission();

        $query = Invoice::where('tenant_id', $tenant->id)
            ->with(['contact', 'paymentAllocations.payment']);

        // Status filter
        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            if ($status === 'due') {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '>=', now());
            } elseif ($status === 'overdue') {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '<', now());
            } else {
                $query->where('status', $status);
            }
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->where('invoice_date', '>=', Carbon::parse($request->date_from));
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('invoice_date', '<=', Carbon::parse($request->date_to));
        }

        // Payment method filter
        if ($request->has('payment_method') && $request->payment_method) {
            $query->whereHas('paymentAllocations.payment', function($q) use ($request) {
                $q->where('payment_method', $request->payment_method);
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get status counts
        $statusCounts = [
            'all' => Invoice::where('tenant_id', $tenant->id)->count(),
            'paid' => Invoice::where('tenant_id', $tenant->id)->where('status', 'paid')->count(),
            'due' => Invoice::where('tenant_id', $tenant->id)
                ->where('status', '!=', 'paid')
                ->where('due_date', '>=', now())
                ->count(),
            'overdue' => Invoice::where('tenant_id', $tenant->id)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->count(),
        ];

        return response()->json([
            'invoices' => $invoices->map(function($invoice) {
                return $this->formatInvoiceData($invoice);
            }),
            'status_counts' => $statusCounts,
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Get invoice detail with full payment narration
     */
    public function show(Tenant $tenant, Invoice $invoice)
    {
        $this->ensurePermission();

        // Ensure invoice belongs to tenant
        if ($invoice->tenant_id !== $tenant->id) {
            abort(404);
        }

        $invoice->load([
            'contact',
            'lineItems',
            'paymentAllocations.payment.subscription',
        ]);

        return response()->json([
            'invoice' => $this->formatInvoiceData($invoice, true),
        ]);
    }

    /**
     * Export invoices to CSV
     */
    public function export(Request $request, Tenant $tenant)
    {
        $this->ensurePermission();

        $query = Invoice::where('tenant_id', $tenant->id)
            ->with(['contact', 'paymentAllocations.payment.subscription']);

        // Apply same filters as index
        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            if ($status === 'due') {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '>=', now());
            } elseif ($status === 'overdue') {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '<', now());
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('invoice_date', '>=', Carbon::parse($request->date_from));
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('invoice_date', '<=', Carbon::parse($request->date_to));
        }

        if ($request->has('payment_method') && $request->payment_method) {
            $query->whereHas('paymentAllocations.payment', function($q) use ($request) {
                $q->where('payment_method', $request->payment_method);
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $filename = "tenant_{$tenant->id}_invoices_" . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Invoice Number',
                'Client Name',
                'Invoice Date',
                'Due Date',
                'Subtotal',
                'Tax',
                'Total',
                'Status',
                'Payment Amount',
                'Plan',
                'Transaction Code',
                'Payment Method',
                'Payment Date',
                'Narration',
            ]);

            // Data rows
            foreach ($invoices as $invoice) {
                $paymentNarration = $this->getPaymentNarration($invoice);
                
                fputcsv($file, [
                    $this->escapeCsvField($invoice->invoice_number),
                    $this->escapeCsvField($invoice->contact->name ?? ''),
                    $invoice->invoice_date->format('d/m/Y'),
                    $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '',
                    number_format($invoice->subtotal, 2),
                    number_format($invoice->tax_amount, 2),
                    number_format($invoice->total, 2),
                    $invoice->status,
                    $paymentNarration['payment_amount'] ?? '',
                    $paymentNarration['plan_name'] ?? '',
                    $paymentNarration['transaction_code'] ?? '',
                    $paymentNarration['payment_method'] ?? '',
                    $paymentNarration['payment_date'] ?? '',
                    $this->escapeCsvField($paymentNarration['narration'] ?? ''),
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Format invoice data for JSON response
     */
    protected function formatInvoiceData(Invoice $invoice, bool $includeFullNarration = false): array
    {
        $paymentNarration = $this->getPaymentNarration($invoice, $includeFullNarration);

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client_name' => $invoice->contact->name ?? 'N/A',
            'client_email' => $invoice->contact->email ?? '',
            'invoice_date' => $invoice->invoice_date->format('d/m/Y'),
            'due_date' => $invoice->due_date ? $invoice->due_date->format('d/m/Y') : null,
            'subtotal' => number_format($invoice->subtotal, 2),
            'tax_amount' => number_format($invoice->tax_amount, 2),
            'total' => number_format($invoice->total, 2),
            'status' => $invoice->status,
            'is_overdue' => $invoice->status !== 'paid' && $invoice->due_date && $invoice->due_date < now(),
            'payment_narration' => $paymentNarration,
        ];
    }

    /**
     * Get payment narration for invoice
     */
    protected function getPaymentNarration(Invoice $invoice, bool $includeFull = false): array
    {
        if ($invoice->status !== 'paid') {
            return [
                'payment_amount' => null,
                'plan_name' => null,
                'transaction_code' => null,
                'payment_method' => null,
                'payment_date' => null,
                'narration' => null,
            ];
        }

        $allocations = $invoice->paymentAllocations;
        if ($allocations->isEmpty()) {
            return [
                'payment_amount' => null,
                'plan_name' => null,
                'transaction_code' => null,
                'payment_method' => null,
                'payment_date' => null,
                'narration' => null,
            ];
        }

        // Get first payment allocation (assuming single payment per invoice for subscription)
        $allocation = $allocations->first();
        $payment = $allocation->payment;

        if (!$payment) {
            return [
                'payment_amount' => null,
                'plan_name' => null,
                'transaction_code' => null,
                'payment_method' => null,
                'payment_date' => null,
                'narration' => null,
            ];
        }

        // Get plan name from subscription if available
        $planName = null;
        if ($payment->subscription) {
            $planName = $this->getPlanName($payment->subscription->plan);
        }

        // Get transaction code
        $transactionCode = $payment->mpesa_receipt 
            ?? $payment->reference 
            ?? $payment->checkout_request_id 
            ?? $payment->merchant_request_id 
            ?? null;

        // Build narration
        $narrationParts = [];
        if ($includeFull) {
            $narrationParts[] = "Payment Amount: KES " . number_format($payment->amount, 2);
            if ($planName) {
                $narrationParts[] = "Plan: {$planName}";
            }
            if ($transactionCode) {
                $narrationParts[] = "Transaction Code: {$transactionCode}";
            }
            if ($payment->payment_method) {
                $narrationParts[] = "Payment Method: " . ucfirst(str_replace('_', ' ', $payment->payment_method));
            }
            if ($payment->payment_date) {
                $narrationParts[] = "Payment Date: " . $payment->payment_date->format('d/m/Y H:i:s');
            }
            if ($payment->notes) {
                $narrationParts[] = "Notes: {$payment->notes}";
            }
        }

        return [
            'payment_amount' => number_format($payment->amount, 2),
            'plan_name' => $planName,
            'transaction_code' => $transactionCode,
            'payment_method' => $payment->payment_method ? ucfirst(str_replace('_', ' ', $payment->payment_method)) : null,
            'payment_date' => $payment->payment_date ? $payment->payment_date->format('d/m/Y H:i:s') : null,
            'narration' => $includeFull ? implode(' | ', $narrationParts) : null,
        ];
    }

    /**
     * Get plan name from plan ID
     */
    protected function getPlanName(?string $plan): ?string
    {
        if (!$plan) {
            return null;
        }

        return match($plan) {
            'plan_free' => 'Free',
            'plan_starter' => 'Starter',
            'plan_business' => 'Business',
            'plan_enterprise' => 'Enterprise',
            default => ucfirst(str_replace('plan_', '', $plan)),
        };
    }

    /**
     * Escape CSV field
     */
    protected function escapeCsvField(?string $field): string
    {
        if ($field === null) {
            return '';
        }

        // Escape quotes and wrap in quotes if contains comma, quote, or newline
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }

        return $field;
    }
}

