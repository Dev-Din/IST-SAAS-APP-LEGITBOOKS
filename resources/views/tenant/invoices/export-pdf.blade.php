<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices Report - {{ $tenant->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            margin: 30px;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.5pt;
            color: #333;
            line-height: 1.5;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #392a26;
            padding: 20px 0 18px 0;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #392a26;
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        .header .company-info {
            color: #6B7280;
            font-size: 8.5pt;
            line-height: 1.6;
            padding-top: 5px;
        }
        .header .company-info strong {
            color: #392a26;
            font-size: 9pt;
        }
        .report-info {
            background-color: #F9FAFB;
            padding: 15px 18px;
            border-radius: 5px;
            margin-bottom: 22px;
            border-left: 3px solid #392a26;
        }
        .report-info table {
            width: 100%;
        }
        .report-info td {
            padding: 6px 0;
        }
        .report-info .label {
            font-weight: bold;
            color: #392a26;
            width: 120px;
            font-size: 8.5pt;
        }
        .report-info .value {
            color: #111827;
            font-size: 8.5pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
            table-layout: fixed;
        }
        thead {
            background-color: #392a26;
            color: white;
        }
        thead th {
            padding: 10px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            border: 1px solid #2a1f1c;
            letter-spacing: 0.2px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        thead th.text-right {
            text-align: right;
        }
        thead th.text-center {
            text-align: center;
        }
        tbody td {
            padding: 8px 6px;
            border: 1px solid #E5E7EB;
            font-size: 8pt;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }
        tbody tr:nth-child(even) {
            background-color: #FAFBFC;
        }
        tbody tr {
            border-bottom: 1px solid #F3F4F6;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.1px;
        }
        .status-draft { background-color: #E5E7EB; color: #374151; }
        .status-sent { background-color: #DBEAFE; color: #1E40AF; }
        .status-paid { background-color: #D1FAE5; color: #065F46; }
        .status-overdue { background-color: #FEE2E2; color: #991B1B; }
        .status-cancelled { background-color: #F3F4F6; color: #6B7280; }
        .summary {
            margin-top: 25px;
            padding: 18px 20px;
            background-color: #FAFBFC;
            border-radius: 5px;
            border: 2px solid #E5E7EB;
        }
        .summary-row {
            display: table;
            width: 100%;
            padding: 7px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        .summary-row:last-child {
            border-bottom: none;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 9.5pt;
            border-top: 3px solid #392a26;
            border-bottom: 3px solid #392a26;
            padding: 12px 0;
            margin-top: 12px;
            background-color: #F9FAFB;
            padding-left: 8px;
            padding-right: 8px;
        }
        .summary-label {
            display: table-cell;
            color: #6B7280;
            font-size: 8.5pt;
            padding-right: 15px;
            width: 60%;
        }
        .summary-value {
            display: table-cell;
            font-weight: bold;
            color: #392a26;
            font-size: 8.5pt;
            text-align: right;
            width: 40%;
        }
        .summary-row.total .summary-label {
            color: #392a26;
            font-size: 9.5pt;
        }
        .summary-row.total .summary-value {
            color: #392a26;
            font-size: 9.5pt;
        }
        .footer {
            margin-top: 30px;
            padding-top: 18px;
            border-top: 2px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 7.5pt;
            line-height: 1.6;
        }
        .footer p {
            margin: 5px 0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoices Report</h1>
        <div class="company-info">
            <strong>{{ $tenant->name }}</strong><br>
            Generated on: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td class="label">Report Period:</td>
                <td class="value">All Invoices</td>
            </tr>
            <tr>
                <td class="label">Total Invoices:</td>
                <td class="value">{{ $invoices->count() }}</td>
            </tr>
            <tr>
                <td class="label">Generated By:</td>
                <td class="value">{{ auth()->user()->name ?? 'System' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Invoice #</th>
                <th style="width: 9%;">Date</th>
                <th style="width: 9%;">Due Date</th>
                <th style="width: 18%;">Customer</th>
                <th style="width: 8%;">Status</th>
                <th class="text-right" style="width: 10%;">Subtotal</th>
                <th class="text-right" style="width: 9%;">Tax</th>
                <th class="text-right" style="width: 10%;">Total</th>
                <th class="text-right" style="width: 10%;">Outstanding</th>
                <th style="width: 5%;">Payment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            @php
                $outstanding = $invoice->getOutstandingAmount();
                $paymentStatus = $outstanding <= 0 ? 'Paid' : ($outstanding < $invoice->total ? 'Partial' : 'Unpaid');
            @endphp
            <tr>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</td>
                <td>{{ $invoice->contact->name }}</td>
                <td>
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
                <td class="text-right">KES {{ number_format($invoice->subtotal, 2) }}</td>
                <td class="text-right">KES {{ number_format($invoice->tax_amount, 2) }}</td>
                <td class="text-right"><strong>KES {{ number_format($invoice->total, 2) }}</strong></td>
                <td class="text-right">KES {{ number_format($outstanding, 2) }}</td>
                <td class="text-center">{{ $paymentStatus }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 30px; color: #6B7280; font-size: 8.5pt;">
                    No invoices found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($invoices->count() > 0)
    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Total Invoices:</span>
            <span class="summary-value">{{ $invoices->count() }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Invoiced Amount:</span>
            <span class="summary-value">KES {{ number_format($invoices->sum('total'), 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Outstanding:</span>
            <span class="summary-value">KES {{ number_format($invoices->sum(function($inv) { return $inv->getOutstandingAmount(); }), 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Paid:</span>
            <span class="summary-value">KES {{ number_format($invoices->sum('total') - $invoices->sum(function($inv) { return $inv->getOutstandingAmount(); }), 2) }}</span>
        </div>
        <div class="summary-row total">
            <span class="summary-label">Net Amount:</span>
            <span class="summary-value">KES {{ number_format($invoices->sum('total'), 2) }}</span>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report. No signature is required.</p>
        <p>Generated by <strong>{{ $tenant->name }}</strong> - LegitBooks Accounting System</p>
    </div>
</body>
</html>
