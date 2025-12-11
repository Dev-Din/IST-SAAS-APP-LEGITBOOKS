<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display the reports index page
     */
    public function index(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Revenue Summary
        $revenueSummary = $this->getRevenueSummary($tenant->id, $dateFrom, $dateTo);
        
        // Payment Collection
        $paymentCollection = $this->getPaymentCollection($tenant->id, $dateFrom, $dateTo);
        
        // Invoice Summary
        $invoiceSummary = $this->getInvoiceSummary($tenant->id, $dateFrom, $dateTo);

        return view('tenant.reports.index', compact(
            'revenueSummary',
            'paymentCollection',
            'invoiceSummary',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Get Revenue Summary for tenant
     */
    protected function getRevenueSummary(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Total revenue from invoices
        $totalRevenue = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('total');

        // Paid revenue
        $paidRevenue = Payment::where('tenant_id', $tenantId)
            ->where('transaction_status', 'completed')
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('amount');

        // Outstanding revenue
        $outstandingRevenue = $totalRevenue - $paidRevenue;

        // Revenue trend (daily breakdown)
        $revenueTrend = [];
        $currentDate = $dateFromCarbon->copy();
        while ($currentDate <= $dateToCarbon) {
            $dailyRevenue = Payment::where('tenant_id', $tenantId)
                ->where('transaction_status', 'completed')
                ->whereDate('payment_date', $currentDate->format('Y-m-d'))
                ->sum('amount');
            
            $revenueTrend[] = [
                'date' => $currentDate->format('d/m/Y'),
                'revenue' => $dailyRevenue ?? 0,
            ];
            
            $currentDate->addDay();
        }

        return [
            'total_revenue' => $totalRevenue ?? 0,
            'paid_revenue' => $paidRevenue ?? 0,
            'outstanding_revenue' => $outstandingRevenue ?? 0,
            'revenue_trend' => $revenueTrend,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Get Payment Collection for tenant
     */
    protected function getPaymentCollection(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Total collected
        $totalCollected = Payment::where('tenant_id', $tenantId)
            ->where('transaction_status', 'completed')
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('amount');

        // Outstanding invoices - calculate using payment allocations
        $outstanding = Invoice::where('tenant_id', $tenantId)
            ->where('status', '!=', 'paid')
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->get()
            ->sum(function ($invoice) {
                return $invoice->getOutstandingAmount();
            });

        // Collection rate
        $totalInvoiced = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('total');
        
        $collectionRate = $totalInvoiced > 0 ? ($totalCollected / $totalInvoiced) * 100 : 0;

        // Payments by status
        $byStatus = Payment::where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->select('transaction_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('transaction_status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->transaction_status => [
                    'count' => $item->count,
                    'total' => $item->total ?? 0,
                ]];
            })
            ->toArray();

        // Payments by method
        $byMethod = Payment::where('tenant_id', $tenantId)
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->where('transaction_status', 'completed')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => [
                    'count' => $item->count,
                    'total' => $item->total ?? 0,
                ]];
            })
            ->toArray();

        return [
            'total_collected' => $totalCollected ?? 0,
            'outstanding' => $outstanding ?? 0,
            'collection_rate' => round($collectionRate, 2),
            'by_status' => $byStatus,
            'by_method' => $byMethod,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Get Invoice Summary for tenant
     */
    protected function getInvoiceSummary(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Total invoices
        $totalInvoices = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->count();

        // Total invoiced amount
        $totalInvoiced = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('total');

        // Invoices by status
        $byStatus = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'total' => $item->total ?? 0,
                ]];
            })
            ->toArray();

        // Average invoice value
        $averageInvoice = $totalInvoices > 0 ? ($totalInvoiced / $totalInvoices) : 0;

        return [
            'total_invoices' => $totalInvoices,
            'total_invoiced' => $totalInvoiced ?? 0,
            'average_invoice' => round($averageInvoice, 2),
            'by_status' => $byStatus,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
