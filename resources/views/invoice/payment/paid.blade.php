<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} - Paid</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="bg-green-600 px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Payment Confirmed</h1>
                </div>
                <div class="px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Invoice {{ $invoice->invoice_number }} is Paid</h2>
                    <p class="text-gray-600 mb-6">Thank you for your payment. A receipt has been sent to your email.</p>
                    <a href="/" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">
                        Return Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

