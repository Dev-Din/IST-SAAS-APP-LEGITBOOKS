<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use App\Services\Mail\PHPMailerService;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-scheduled 
                            {--frequency=daily : Report frequency (daily, weekly, monthly)}
                            {--report=all : Report type (tenant_overview, revenue, subscription, payment, all)}
                            {--format=pdf : Export format (csv, excel, pdf)}
                            {--email= : Email address to send reports to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and optionally email scheduled reports';

    /**
     * Execute the console command.
     */
    public function handle(PHPMailerService $mailer): int
    {
        $frequency = $this->option('frequency');
        $reportType = $this->option('report');
        $format = $this->option('format');
        $email = $this->option('email');

        $this->info("Generating {$frequency} reports...");

        // Determine date range based on frequency
        $dateRange = $this->getDateRange($frequency);
        $dateFrom = $dateRange['from'];
        $dateTo = $dateRange['to'];

        $reports = $reportType === 'all' 
            ? ['tenant_overview', 'revenue', 'subscription', 'payment']
            : [$reportType];

        $generatedFiles = [];

        foreach ($reports as $report) {
            $this->info("Generating {$report} report...");
            
            // Get report data using reflection to call protected methods
            $controller = new \App\Http\Controllers\Admin\ReportsController();
            $reflection = new \ReflectionClass($controller);
            
            $methodMap = [
                'tenant_overview' => 'getTenantOverview',
                'revenue' => 'getRevenueSummary',
                'subscription' => 'getSubscriptionMetrics',
                'payment' => 'getPaymentCollection',
            ];
            
            if (!isset($methodMap[$report])) {
                $this->error("Unknown report type: {$report}");
                continue;
            }
            
            $method = $reflection->getMethod($methodMap[$report]);
            $method->setAccessible(true);
            
            if ($report === 'revenue' || $report === 'payment') {
                $data = $method->invoke($controller, $dateFrom, $dateTo);
            } else {
                $data = $method->invoke($controller);
            }

            // Generate file based on format
            $filename = $this->generateReportFile($report, $data, $format, $dateFrom, $dateTo);
            
            if ($filename) {
                $generatedFiles[] = [
                    'report' => $report,
                    'filename' => $filename,
                    'path' => Storage::disk('local')->path($filename),
                ];
                $this->info("✓ Generated: {$filename}");
            }
        }

        // Send email if provided
        if ($email && !empty($generatedFiles)) {
            $this->info("Sending reports to {$email}...");
            $this->sendReportEmail($mailer, $email, $generatedFiles, $frequency, $dateFrom, $dateTo);
            $this->info("✓ Reports sent successfully");
        }

        $this->info("Report generation completed!");

        return Command::SUCCESS;
    }

    /**
     * Get date range based on frequency
     */
    protected function getDateRange(string $frequency): array
    {
        switch ($frequency) {
            case 'daily':
                return [
                    'from' => now()->subDay()->startOfDay()->format('Y-m-d'),
                    'to' => now()->subDay()->endOfDay()->format('Y-m-d'),
                ];
            case 'weekly':
                return [
                    'from' => now()->subWeek()->startOfWeek()->format('Y-m-d'),
                    'to' => now()->subWeek()->endOfWeek()->format('Y-m-d'),
                ];
            case 'monthly':
                return [
                    'from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
                    'to' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
                ];
            default:
                return [
                    'from' => now()->startOfMonth()->format('Y-m-d'),
                    'to' => now()->format('Y-m-d'),
                ];
        }
    }

    /**
     * Generate report file
     */
    protected function generateReportFile(string $report, array $data, string $format, string $dateFrom, string $dateTo): ?string
    {
        $filename = "reports/{$report}_{$dateFrom}_{$dateTo}_" . now()->format('Y-m-d_His') . ".{$format}";

        try {
            switch ($format) {
                case 'csv':
                    $content = $this->generateCsvContent($data, $report);
                    Storage::disk('local')->put($filename, $content);
                    break;
                case 'excel':
                    if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
                        \Maatwebsite\Excel\Facades\Excel::store(
                            new \App\Exports\ReportExport($data, $report),
                            $filename,
                            'local'
                        );
                    } else {
                        $this->warn("Excel export not available, falling back to CSV");
                        $content = $this->generateCsvContent($data, $report);
                        Storage::disk('local')->put(str_replace('.xlsx', '.csv', $filename), $content);
                        $filename = str_replace('.xlsx', '.csv', $filename);
                    }
                    break;
                case 'pdf':
                    $html = view('admin.reports.pdf', compact('data', 'report', 'dateFrom', 'dateTo'))->render();
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    Storage::disk('local')->put($filename, $pdf->output());
                    break;
                default:
                    $this->error("Unsupported format: {$format}");
                    return null;
            }

            return $filename;
        } catch (\Exception $e) {
            $this->error("Failed to generate {$report} report: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate CSV content
     */
    protected function generateCsvContent(array $data, string $report): string
    {
        $output = fopen('php://temp', 'r+');

        // Write headers
        switch ($report) {
            case 'tenant_overview':
                fputcsv($output, ['Metric', 'Value']);
                fputcsv($output, ['Total Tenants', $data['total'] ?? 0]);
                fputcsv($output, ['Active Tenants', $data['active'] ?? 0]);
                fputcsv($output, ['Suspended Tenants', $data['suspended'] ?? 0]);
                fputcsv($output, ['Trial Tenants', $data['trial'] ?? 0]);
                break;
            case 'revenue':
                fputcsv($output, ['Date', 'Revenue']);
                if (isset($data['revenue_trend'])) {
                    foreach ($data['revenue_trend'] as $trend) {
                        fputcsv($output, [$trend['date'], $trend['revenue']]);
                    }
                }
                break;
            case 'subscription':
                fputcsv($output, ['Plan', 'Count']);
                if (isset($data['by_plan'])) {
                    foreach ($data['by_plan'] as $plan => $count) {
                        fputcsv($output, [$plan, $count]);
                    }
                }
                break;
            case 'payment':
                fputcsv($output, ['Status', 'Count', 'Total Amount']);
                if (isset($data['by_status'])) {
                    foreach ($data['by_status'] as $status => $info) {
                        fputcsv($output, [$status, $info['count'] ?? 0, $info['total'] ?? 0]);
                    }
                }
                break;
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Send report email
     */
    protected function sendReportEmail(PHPMailerService $mailer, string $email, array $files, string $frequency, string $dateFrom, string $dateTo): void
    {
        $html = view('emails.admin.scheduled-reports', compact('files', 'frequency', 'dateFrom', 'dateTo'))->render();
        $text = "Scheduled {$frequency} reports for period {$dateFrom} to {$dateTo}";

        $attachments = array_map(function ($file) {
            return $file['path'];
        }, $files);

        $mailer->send([
            'to' => $email,
            'subject' => "LegitBooks {$frequency} Reports - " . now()->format('M d, Y'),
            'html' => $html,
            'text' => $text,
            'attachments' => $attachments,
            'from_name' => 'LegitBooks Reports',
        ]);
    }
}

