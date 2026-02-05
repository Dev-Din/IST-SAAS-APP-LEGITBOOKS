<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="bg-red-600 px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Payment Failed</h1>
                </div>
                <div class="px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Payment could not be completed</h2>
                    <p class="text-gray-600 mb-4">The M-Pesa payment for Invoice {{ $invoice->invoice_number }} was not successful. You can try again below.</p>
                    <div class="flex flex-wrap gap-3 justify-center">
                        <a href="{{ route('invoice.pay', [$invoice->id, $invoice->payment_token]) }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-medium">
                            Try again
                        </a>
                        <a href="{{ url('/') }}" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900 font-medium">
                            Return Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
