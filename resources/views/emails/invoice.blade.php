<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; }
        .card { background: #ffffff; padding: 20px; border-radius: 8px; }
        .btn { background-color: #0f172a; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Invoice {{ $invoice->invoice_number }}</h2>
        <p>Hello {{ $invoice->contact->name }},</p>
        <p>You have a new invoice for {{ number_format($invoice->total, 2) }} due on {{ optional($invoice->due_date)->format('d/m/Y') }}.</p>
        <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        <a href="{{ url('app/invoices/' . $invoice->id) }}" class="btn">View Invoice</a>
    </div>
</body>
</html>
