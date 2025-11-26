<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
    <div style="background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="margin-bottom: 30px;">
            <h1 style="color: #392a26; margin-top: 0; font-size: 24px;">Invoice {{ $invoice->invoice_number }}</h1>
        </div>

        <p style="font-size: 16px; margin-bottom: 20px;">
            Hello {{ $contact->name }},
        </p>

        <p style="font-size: 16px; margin-bottom: 20px;">
            Please find your invoice attached for <strong>{{ $tenant->name }}</strong>.
        </p>

        <div style="background-color: #f9f9f9; padding: 20px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0; font-size: 18px; font-weight: bold; color: #392a26;">
                Amount Due: KES {{ number_format($invoice->total, 2) }}
            </p>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">
                Subtotal: KES {{ number_format($invoice->subtotal, 2) }}
                @if($invoice->tax_amount > 0)
                <br>Tax: KES {{ number_format($invoice->tax_amount, 2) }}
                @endif
            </p>
        </div>

        @if($invoice->due_date)
        <p style="font-size: 14px; color: #666; margin: 20px 0;">
            This invoice is due on <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>.
        </p>
        @endif

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $paymentUrl }}" style="display: inline-block; padding: 12px 30px; background-color: #392a26; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                Pay Invoice Online
            </a>
        </div>

        <div style="margin: 30px 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                <strong>Payment Options:</strong><br>
                • M-Pesa: Click the payment link above to initiate STK push<br>
                • Debit/Credit Card: Available through the payment portal (coming soon)<br>
                • PayPal: Available through the payment portal (coming soon)
            </p>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            You can download the invoice PDF from the attachment or by clicking the payment link above.
        </p>

        <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">

        <p style="font-size: 14px; color: #666; margin-bottom: 0;">
            Regards,<br>
            <strong>{{ $tenant->name }}</strong><br>
            via LegitBooks
        </p>
    </div>
</body>
</html>

