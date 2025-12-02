<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Check if the authenticated admin has permission to view reports
     */
    protected function ensurePermission(): void
    {
        $admin = auth('admin')->user();
        if (!$admin || (!$admin->hasRole('owner') && !$admin->hasPermission('reports.view'))) {
            abort(403, 'You do not have permission to view reports.');
        }
    }

    /**
     * Display the reports index page
     */
    public function index(Request $request)
    {
        $this->ensurePermission();

        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $compareFrom = $request->get('compare_from');
        $compareTo = $request->get('compare_to');

        // Phase 1 Reports Data
        $tenantOverview = $this->getTenantOverview();
        $revenueSummary = $this->getRevenueSummary($dateFrom, $dateTo);
        $subscriptionMetrics = $this->getSubscriptionMetrics();
        $paymentCollection = $this->getPaymentCollection($dateFrom, $dateTo);

        // Comparison data if dates provided
        $comparisonData = null;
        if ($compareFrom && $compareTo) {
            $comparisonData = [
                'revenue' => $this->getRevenueSummary($compareFrom, $compareTo),
                'payments' => $this->getPaymentCollection($compareFrom, $compareTo),
            ];
        }

        return view('admin.reports.index', compact(
            'tenantOverview',
            'revenueSummary',
            'subscriptionMetrics',
            'paymentCollection',
            'dateFrom',
            'dateTo',
            'compareFrom',
            'compareTo',
            'comparisonData'
        ));
    }

    /**
     * Export report data
     */
    public function export(Request $request)
    {
        $this->ensurePermission();

        $type = $request->get('type', 'csv'); // csv, pdf, excel
        $report = $request->get('report'); // tenant_overview, revenue, subscription, payment
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $data = $this->getExportData($report, $dateFrom, $dateTo);

        switch ($type) {
            case 'csv':
                return $this->exportCsv($data, $report);
            case 'excel':
                return $this->exportExcel($data, $report);
            case 'pdf':
                return $this->exportPdf($data, $report, $dateFrom, $dateTo);
            default:
                return redirect()->back()->with('error', 'Invalid export type.');
        }
    }

    /**
     * Get Tenant Overview Data
     */
    protected function getTenantOverview(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $trialTenants = Subscription::where('status', 'trial')->count();

        // New tenants this month
        $newThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // New tenants last month
        $newLastMonth = Tenant::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Growth trend (last 6 months)
        $growthTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $growthTrend[] = [
                'month' => $date->format('M Y'),
                'count' => Tenant::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        }

        return [
            'total' => $totalTenants,
            'active' => $activeTenants,
            'suspended' => $suspendedTenants,
            'trial' => $trialTenants,
            'new_this_month' => $newThisMonth,
            'new_last_month' => $newLastMonth,
            'growth_trend' => $growthTrend,
        ];
    }

    /**
     * Get Revenue Summary
     */
    protected function getRevenueSummary(string $dateFrom, string $dateTo): array
    {
        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Total revenue from subscription payments (across all tenants)
        $totalRevenue = DB::table('payments')
            ->where('transaction_status', 'completed')
            ->whereNotNull('subscription_id')
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('amount');

        // Revenue by plan
        $revenueByPlan = DB::table('payments')
            ->where('payments.transaction_status', 'completed')
            ->whereNotNull('payments.subscription_id')
            ->whereBetween('payments.payment_date', [$dateFromCarbon, $dateToCarbon])
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->select('subscriptions.plan', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('subscriptions.plan')
            ->get()
            ->pluck('total', 'plan')
            ->toArray();

        // Monthly recurring revenue (MRR)
        $mrr = DB::table('subscriptions')
            ->where('subscriptions.status', 'active')
            ->selectRaw('SUM(CASE 
                WHEN subscriptions.plan = "plan_starter" THEN 2500
                WHEN subscriptions.plan = "plan_business" THEN 5000
                ELSE 0
            END) as mrr')
            ->first()->mrr ?? 0;

        // Average revenue per tenant (ARPU)
        $activeTenantCount = Tenant::where('status', 'active')->count();
        $arpu = $activeTenantCount > 0 ? ($mrr / $activeTenantCount) : 0;

        // Revenue trend (daily breakdown)
        $revenueTrend = [];
        $currentDate = $dateFromCarbon->copy();
        while ($currentDate <= $dateToCarbon) {
            $dailyRevenue = DB::table('payments')
                ->where('transaction_status', 'completed')
                ->whereNotNull('subscription_id')
                ->whereDate('payment_date', $currentDate)
                ->sum('amount');

            $revenueTrend[] = [
                'date' => $currentDate->format('Y-m-d'),
                'revenue' => $dailyRevenue ?? 0,
            ];

            $currentDate->addDay();
        }

        return [
            'total_revenue' => $totalRevenue,
            'revenue_by_plan' => $revenueByPlan,
            'mrr' => $mrr,
            'arpu' => $arpu,
            'revenue_trend' => $revenueTrend,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Get Subscription Metrics
     */
    protected function getSubscriptionMetrics(): array
    {
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $trialSubscriptions = Subscription::where('status', 'trial')->count();
        $cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();
        $expiredSubscriptions = Subscription::where('status', 'expired')->count();

        // Subscriptions by plan
        $byPlan = Subscription::select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->get()
            ->pluck('count', 'plan')
            ->toArray();

        // Churn rate (cancelled in last 30 days / active at start of period)
        $cancelledLast30Days = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        $activeAtStart = Subscription::where('status', 'active')
            ->where('updated_at', '<', now()->subDays(30))
            ->count();

        $churnRate = $activeAtStart > 0 ? ($cancelledLast30Days / $activeAtStart) * 100 : 0;

        // Trial to paid conversion
        $trialsStarted = Subscription::where('status', 'trial')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $convertedToPaid = Subscription::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>=', now()->subDays(30))
            ->where('trial_ends_at', '<=', now())
            ->count();

        $conversionRate = $trialsStarted > 0 ? ($convertedToPaid / $trialsStarted) * 100 : 0;

        return [
            'total' => $totalSubscriptions,
            'active' => $activeSubscriptions,
            'trial' => $trialSubscriptions,
            'cancelled' => $cancelledSubscriptions,
            'expired' => $expiredSubscriptions,
            'by_plan' => $byPlan,
            'churn_rate' => round($churnRate, 2),
            'conversion_rate' => round($conversionRate, 2),
        ];
    }

    /**
     * Get Payment Collection Data
     */
    protected function getPaymentCollection(string $dateFrom, string $dateTo): array
    {
        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // Total payments collected
        $totalCollected = DB::table('payments')
            ->where('transaction_status', 'completed')
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('amount');

        // Outstanding payments (pending/failed)
        $outstanding = DB::table('payments')
            ->whereIn('transaction_status', ['pending', 'failed'])
            ->whereBetween('payment_date', [$dateFromCarbon, $dateToCarbon])
            ->sum('amount');

        // Collection rate
        $totalExpected = $totalCollected + $outstanding;
        $collectionRate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

        // Payments by status
        $byStatus = DB::table('payments')
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
        $byMethod = DB::table('payments')
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
            'total_collected' => $totalCollected,
            'outstanding' => $outstanding,
            'collection_rate' => round($collectionRate, 2),
            'by_status' => $byStatus,
            'by_method' => $byMethod,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Get data for export
     */
    public function getExportData(string $report, string $dateFrom, string $dateTo): array
    {
        switch ($report) {
            case 'tenant_overview':
                return $this->getTenantOverview();
            case 'revenue':
                return $this->getRevenueSummary($dateFrom, $dateTo);
            case 'subscription':
                return $this->getSubscriptionMetrics();
            case 'payment':
                return $this->getPaymentCollection($dateFrom, $dateTo);
            default:
                return [];
        }
    }

    /**
     * Export to CSV
     */
    protected function exportCsv(array $data, string $report): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = "{$report}_report_" . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($data, $report) {
            $file = fopen('php://output', 'w');

            // Write headers based on report type
            $this->writeCsvHeaders($file, $report);

            // Write data
            $this->writeCsvData($file, $data, $report);

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Write CSV headers
     */
    protected function writeCsvHeaders($file, string $report): void
    {
        switch ($report) {
            case 'tenant_overview':
                fputcsv($file, ['Metric', 'Value']);
                break;
            case 'revenue':
                fputcsv($file, ['Date', 'Revenue']);
                break;
            case 'subscription':
                fputcsv($file, ['Plan', 'Count']);
                break;
            case 'payment':
                fputcsv($file, ['Status', 'Count', 'Total Amount']);
                break;
        }
    }

    /**
     * Write CSV data
     */
    protected function writeCsvData($file, array $data, string $report): void
    {
        switch ($report) {
            case 'tenant_overview':
                fputcsv($file, ['Total Tenants', $data['total'] ?? 0]);
                fputcsv($file, ['Active Tenants', $data['active'] ?? 0]);
                fputcsv($file, ['Suspended Tenants', $data['suspended'] ?? 0]);
                fputcsv($file, ['Trial Tenants', $data['trial'] ?? 0]);
                break;
            case 'revenue':
                if (isset($data['revenue_trend'])) {
                    foreach ($data['revenue_trend'] as $trend) {
                        fputcsv($file, [$trend['date'], $trend['revenue']]);
                    }
                }
                break;
            case 'subscription':
                if (isset($data['by_plan'])) {
                    foreach ($data['by_plan'] as $plan => $count) {
                        fputcsv($file, [$plan, $count]);
                    }
                }
                break;
            case 'payment':
                if (isset($data['by_status'])) {
                    foreach ($data['by_status'] as $status => $info) {
                        fputcsv($file, [$status, $info['count'] ?? 0, $info['total'] ?? 0]);
                    }
                }
                break;
        }
    }

    /**
     * Export to Excel
     */
    protected function exportExcel(array $data, string $report): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Using Laravel Excel if available, otherwise fallback to CSV
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ReportExport($data, $report),
                "{$report}_report_" . now()->format('Y-m-d_His') . '.xlsx'
            );
        }

        // Fallback to CSV if Excel not available
        return $this->exportCsv($data, $report);
    }

    /**
     * Export to PDF
     */
    protected function exportPdf(array $data, string $report, string $dateFrom, string $dateTo): \Illuminate\Http\Response
    {
        $html = view('admin.reports.pdf', compact('data', 'report', 'dateFrom', 'dateTo'))->render();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $filename = "{$report}_report_" . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}

