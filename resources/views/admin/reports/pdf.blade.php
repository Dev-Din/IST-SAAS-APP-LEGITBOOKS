<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst(str_replace('_', ' ', $report)) }} Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { border-bottom: 2px solid #392a26; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #392a26; }
        .header .meta { color: #666; font-size: 10px; margin-top: 5px; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #392a26; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        table th { background-color: #f9fafb; font-weight: bold; }
        .metric { display: inline-block; margin: 10px 20px 10px 0; }
        .metric-label { font-size: 10px; color: #666; }
        .metric-value { font-size: 18px; font-weight: bold; color: #392a26; }
        .footer { margin-top: 50px; font-size: 10px; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ucfirst(str_replace('_', ' ', $report)) }} Report</h1>
        <div class="meta">
            Generated: {{ now()->format('d/m/Y H:i:s') }}<br>
            Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
        </div>
    </div>

    @if($report === 'tenant_overview')
    <div class="section">
        <h2>Tenant Overview</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Total Tenants</td>
                <td>{{ number_format($data['total'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Active Tenants</td>
                <td>{{ number_format($data['active'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Suspended Tenants</td>
                <td>{{ number_format($data['suspended'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Trial Tenants</td>
                <td>{{ number_format($data['trial'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>New This Month</td>
                <td>{{ number_format($data['new_this_month'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>New Last Month</td>
                <td>{{ number_format($data['new_last_month'] ?? 0) }}</td>
            </tr>
        </table>
    </div>
    @endif

    @if($report === 'revenue')
    <div class="section">
        <h2>Revenue Summary</h2>
        <div class="metric">
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value">KES {{ number_format($data['total_revenue'] ?? 0, 2) }}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Monthly Recurring Revenue (MRR)</div>
            <div class="metric-value">KES {{ number_format($data['mrr'] ?? 0, 2) }}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Average Revenue Per Tenant (ARPU)</div>
            <div class="metric-value">KES {{ number_format($data['arpu'] ?? 0, 2) }}</div>
        </div>
        @if(!empty($data['revenue_by_plan']))
        <table>
            <tr>
                <th>Plan</th>
                <th>Revenue</th>
            </tr>
            @foreach($data['revenue_by_plan'] as $plan => $amount)
            <tr>
                <td>{{ ucfirst(str_replace('plan_', '', $plan)) }}</td>
                <td>KES {{ number_format($amount, 2) }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

    @if($report === 'subscription')
    <div class="section">
        <h2>Subscription Metrics</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Total Subscriptions</td>
                <td>{{ number_format($data['total'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Active</td>
                <td>{{ number_format($data['active'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Trial</td>
                <td>{{ number_format($data['trial'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Cancelled</td>
                <td>{{ number_format($data['cancelled'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Expired</td>
                <td>{{ number_format($data['expired'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Churn Rate (Last 30 Days)</td>
                <td>{{ $data['churn_rate'] ?? 0 }}%</td>
            </tr>
            <tr>
                <td>Trial to Paid Conversion</td>
                <td>{{ $data['conversion_rate'] ?? 0 }}%</td>
            </tr>
        </table>
        @if(!empty($data['by_plan']))
        <table>
            <tr>
                <th>Plan</th>
                <th>Count</th>
            </tr>
            @foreach($data['by_plan'] as $plan => $count)
            <tr>
                <td>{{ ucfirst(str_replace('plan_', '', $plan)) }}</td>
                <td>{{ $count }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

    @if($report === 'payment')
    <div class="section">
        <h2>Payment Collection</h2>
        <div class="metric">
            <div class="metric-label">Total Collected</div>
            <div class="metric-value">KES {{ number_format($data['total_collected'] ?? 0, 2) }}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Outstanding</div>
            <div class="metric-value">KES {{ number_format($data['outstanding'] ?? 0, 2) }}</div>
        </div>
        <div class="metric">
            <div class="metric-label">Collection Rate</div>
            <div class="metric-value">{{ $data['collection_rate'] ?? 0 }}%</div>
        </div>
        @if(!empty($data['by_status']))
        <table>
            <tr>
                <th>Status</th>
                <th>Count</th>
                <th>Total Amount</th>
            </tr>
            @foreach($data['by_status'] as $status => $info)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                <td>{{ $info['count'] ?? 0 }}</td>
                <td>KES {{ number_format($info['total'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </table>
        @endif
        @if(!empty($data['by_method']))
        <table>
            <tr>
                <th>Payment Method</th>
                <th>Count</th>
                <th>Total Amount</th>
            </tr>
            @foreach($data['by_method'] as $method => $info)
            <tr>
                <td>{{ ucfirst(str_replace('_', ' ', $method)) }}</td>
                <td>{{ $info['count'] ?? 0 }}</td>
                <td>KES {{ number_format($info['total'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Generated by LegitBooks Admin Portal</p>
        <p>This is an automated report. For questions, contact support.</p>
    </div>
</body>
</html>

