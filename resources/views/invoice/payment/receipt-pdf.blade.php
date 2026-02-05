<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { border-bottom: 2px solid #059669; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { color: #059669; font-size: 24px; margin: 0 0 5px 0; }
        .header p { margin: 0; color: #6b7280; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 10px 0; color: #1f2937; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .info-label { color: #6b7280; font-size: 11px; }
        .info-value { font-weight: bold; color: #1f2937; }
        .totals-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 15px; margin: 20px 0; }
        .totals-box table { width: 100%; }
        .totals-box td { padding: 5px; }
        .totals-box .total-row { font-size: 16px; font-weight: bold; border-top: 2px solid #059669; padding-top: 8px; }
        .payment-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .payment-table th { background: #f3f4f6; padding: 8px; text-align: left; font-size: 11px; border: 1px solid #e5e7eb; }
        .payment-table td { padding: 8px; border: 1px solid #e5e7eb; font-size: 11px; }
        .payment-receipt { color: #059669; font-weight: bold; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280; text-align: center; }
        .paid-stamp { color: #059669; font-size: 18px; font-weight: bold; text-align: center; padding: 10px; margin: 20px 0; border: 3px solid #059669; background: #ecfdf5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PAYMENT RECEIPT</h1>
        <p>{{ $tenant->name }}</p>
        @if($tenant->email)
        <p>{{ $tenant->email }}</p>
        @endif
    </div>

    <div class="paid-stamp">
        PAID
    </div>

    <div class="section-title">Invoice Information</div>
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <span class="info-label">Invoice Number:</span><br>
                <span class="info-value">{{ $invoice->invoice_number }}</span>
            </td>
            <td style="width: 50%;">
                <span class="info-label">Invoice Date:</span><br>
                <span class="info-value">{{ $invoice->invoice_date->format('d/m/Y') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Customer:</span><br>
                <span class="info-value">{{ $invoice->contact->name }}</span><br>
                @if($invoice->contact->email)
                <span style="color: #6b7280; font-size: 11px;">{{ $invoice->contact->email }}</span>
                @endif
            </td>
            <td>
                <span class="info-label">Status:</span><br>
                <span class="info-value" style="color: #059669;">PAID</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Invoice Summary</div>
    <div class="totals-box">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">KES {{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td style="text-align: right;">KES {{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total Amount:</td>
                <td style="text-align: right;">KES {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Payment Details</div>
    @if($invoice->paymentAllocations->count() > 0)
    <table class="payment-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Payment Method</th>
                <th>Reference</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->paymentAllocations as $allocation)
            <tr>
                <td>{{ $allocation->payment->payment_date->format('d/m/Y') }}</td>
                <td>{{ $allocation->payment->created_at->format('H:i:s') }}</td>
                <td>{{ strtoupper($allocation->payment->payment_method ?? 'M-Pesa') }}</td>
                <td>
                    @if($allocation->payment->mpesa_receipt)
                        <span class="payment-receipt">{{ $allocation->payment->mpesa_receipt }}</span>
                    @else
                        {{ $allocation->payment->payment_number }}
                    @endif
                </td>
                <td style="text-align: right;">KES {{ number_format($allocation->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #6b7280; font-style: italic;">No payment details available.</p>
    @endif

    @php
        $totalPaid = $invoice->paymentAllocations->sum('amount');
    @endphp
    <div style="text-align: right; margin-top: 10px; font-size: 14px;">
        <strong>Total Paid: KES {{ number_format($totalPaid, 2) }}</strong>
    </div>

    <div class="footer">
        <p>Thank you for your payment.</p>
        <p style="margin-top: 10px;">
            Receipt generated on {{ now()->format('d/m/Y') }} at {{ now()->format('H:i:s') }}
        </p>
        @php
            $brandMode = $tenant->getBrandingMode();
        @endphp
        @if($brandMode !== 'C')
            <p style="margin-top: 10px;">
                @php
                    $brandName = $brandMode === 'B' ? 'LegitBooks' : $tenant->name;
                @endphp
                Powered by {{ $brandName }}
            </p>
        @endif
    </div>
</body>
</html>
