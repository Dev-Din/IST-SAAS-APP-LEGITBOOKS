<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Received - Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 30px;">
            <h1 style="color: #392a26; margin-top: 0; font-size: 24px;">Payment Received</h1>
        </div>

        <p style="font-size: 16px; margin-bottom: 20px;">
            Hello {{ $contact->name }},
        </p>

        <p style="font-size: 16px; margin-bottom: 20px;">
            Thank you for your payment. We have received payment for <strong>Invoice {{ $invoice->invoice_number }}</strong>.
        </p>

        <div style="background-color: #d4edda; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #28a745;">
            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #155724;">
                Amount Paid: KES {{ number_format($payment->amount, 2) }}
            </p>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #155724;">
                Payment Method: {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}<br>
                @if($payment->reference)
                Reference: {{ $payment->reference }}<br>
                @endif
                Date: {{ $payment->payment_date->format('d/m/Y H:i:s') }}
            </p>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 20px;">
            Invoice Details:<br>
            Invoice Number: {{ $invoice->invoice_number }}<br>
            Invoice Date: {{ $invoice->invoice_date->format('d/m/Y') }}<br>
            Total Amount: KES {{ number_format($invoice->total, 2) }}
        </p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

        <p style="font-size: 14px; color: #666; margin-bottom: 0;">
            Thank you for your business!<br>
            <strong>{{ $tenant->name }}</strong><br>
            via LegitBooks
        </p>
    </div>
</body>
</html>

