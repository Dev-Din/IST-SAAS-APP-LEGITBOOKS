<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExport implements FromArray, WithHeadings, WithTitle
{
    protected array $data;

    protected string $report;

    public function __construct(array $data, string $report)
    {
        $this->data = $data;
        $this->report = $report;
    }

    public function array(): array
    {
        return $this->formatDataForExport();
    }

    public function headings(): array
    {
        return $this->getHeadings();
    }

    public function title(): string
    {
        return ucfirst(str_replace('_', ' ', $this->report));
    }

    protected function formatDataForExport(): array
    {
        switch ($this->report) {
            case 'tenant_overview':
                return [
                    ['Total Tenants', $this->data['total'] ?? 0],
                    ['Active Tenants', $this->data['active'] ?? 0],
                    ['Suspended Tenants', $this->data['suspended'] ?? 0],
                    ['Trial Tenants', $this->data['trial'] ?? 0],
                    ['New This Month', $this->data['new_this_month'] ?? 0],
                    ['New Last Month', $this->data['new_last_month'] ?? 0],
                ];
            case 'revenue':
                $rows = [];
                if (isset($this->data['revenue_trend'])) {
                    foreach ($this->data['revenue_trend'] as $trend) {
                        $rows[] = [$trend['date'], $trend['revenue']];
                    }
                }

                return $rows;
            case 'subscription':
                $rows = [];
                if (isset($this->data['by_plan'])) {
                    foreach ($this->data['by_plan'] as $plan => $count) {
                        $rows[] = [$plan, $count];
                    }
                }

                return $rows;
            case 'payment':
                $rows = [];
                if (isset($this->data['by_status'])) {
                    foreach ($this->data['by_status'] as $status => $info) {
                        $rows[] = [$status, $info['count'] ?? 0, $info['total'] ?? 0];
                    }
                }

                return $rows;
            default:
                return [];
        }
    }

    protected function getHeadings(): array
    {
        switch ($this->report) {
            case 'tenant_overview':
                return ['Metric', 'Value'];
            case 'revenue':
                return ['Date', 'Revenue'];
            case 'subscription':
                return ['Plan', 'Count'];
            case 'payment':
                return ['Status', 'Count', 'Total Amount'];
            default:
                return [];
        }
    }
}
