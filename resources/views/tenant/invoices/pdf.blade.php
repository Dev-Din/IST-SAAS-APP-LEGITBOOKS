<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; padding-bottom: 10px; }
        .details { width: 100%; margin-bottom: 20px; }
        .details td { padding: 5px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        .totals { width: 250px; float: right; }
        .totals td { padding: 5px; }
        .footer { margin-top: 50px; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice {{ $invoice->invoice_number }}</h1>
        <p>Status: {{ ucfirst($invoice->status) }}</p>
    </div>

    <table class="details">
        <tr>
            <td>
                <strong>Billed To:</strong><br>
                {{ $invoice->contact->name }}<br>
                {{ $invoice->contact->email }}<br>
                {{ $invoice->contact->address }}
            </td>
            <td>
                <strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}<br>
                <strong>Due Date:</strong> {{ optional($invoice->due_date)->format('d/m/Y') }}<br>
                <strong>Total:</strong> {{ number_format($invoice->total, 2) }}
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lineItems as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal:</td>
            <td>{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Tax:</td>
            <td>{{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td><strong>{{ number_format($invoice->total, 2) }}</strong></td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <div class="footer">
        @php
            $tenant = app(\App\Services\TenantContext::class)->getTenant();
            $brandMode = $tenant ? $tenant->getBrandingMode() : env('BRANDING_MODE', 'A');
            $brandName = $brandMode === 'B' ? 'LegitBooks' : ($tenant->name ?? 'LegitBooks');
        @endphp
        Thank you for your business.<br>
        Powered by {{ $brandName }}
    </div>
</body>
</html>
