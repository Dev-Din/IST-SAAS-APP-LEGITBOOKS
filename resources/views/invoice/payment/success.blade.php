<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment {{ $isPaid ? 'Success' : 'Processing' }} - Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                @if($isPaid)
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
                    <p class="text-gray-600 mb-4">Thank you for your payment.</p>
                    <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Invoice Number:</p>
                                <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Amount Paid:</p>
                                <p class="font-semibold text-gray-900">KES {{ number_format($invoice->total, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Date:</p>
                                <p class="font-semibold text-gray-900">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Status:</p>
                                <p class="font-semibold text-green-600">Paid</p>
                            </div>
                        </div>
                    </div>
                    @if($invoice->paymentAllocations->count() > 0)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Payment Details</h3>
                        <div class="space-y-2">
                            @foreach($invoice->paymentAllocations as $allocation)
                            <div class="bg-gray-50 rounded p-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Payment Date:</span>
                                    <span class="font-medium">{{ $allocation->payment->payment_date->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-gray-600">Amount:</span>
                                    <span class="font-medium">KES {{ number_format($allocation->amount, 2) }}</span>
                                </div>
                                @if($allocation->payment->mpesa_receipt)
                                <div class="flex justify-between mt-1">
                                    <span class="text-gray-600">M-Pesa Receipt:</span>
                                    <span class="font-medium">{{ $allocation->payment->mpesa_receipt }}</span>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <a href="/" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">
                        Return Home
                    </a>
                </div>
                @else
                <div class="bg-blue-600 px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Payment Processing</h1>
                </div>
                <div class="px-6 py-8 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Payment is Being Processed</h2>
                    <p class="text-gray-600 mb-4">Your payment for Invoice {{ $invoice->invoice_number }} is being processed.</p>
                    @if($outstanding > 0)
                    <p class="text-sm text-gray-500 mb-6">Outstanding: KES {{ number_format($outstanding, 2) }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mb-6">Please wait while we confirm your payment. This page will refresh automatically.</p>
                    @if(isset($recentPayments) && $recentPayments->count() > 0)
                    <div class="bg-yellow-50 rounded-lg p-4 mb-6 text-left">
                        <p class="text-sm font-medium text-yellow-800 mb-2">Pending Payments:</p>
                        <div class="space-y-2">
                            @foreach($recentPayments as $payment)
                            <div class="text-sm text-yellow-700">
                                <p>Payment #{{ $payment->payment_number }} - KES {{ number_format($payment->amount, 2) }}</p>
                                <p class="text-xs">Status: <span class="font-semibold">{{ ucfirst($payment->transaction_status) }}</span></p>
                                @if($payment->checkout_request_id)
                                <p class="text-xs">Request ID: {{ substr($payment->checkout_request_id, 0, 20) }}...</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> If you've completed the payment on your phone, it may take a few moments to be confirmed. The page will automatically update when payment is confirmed.
                        </p>
                    </div>
                    <a href="/pay/{{ $invoice->id }}/{{ $invoice->payment_token }}" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">
                        Return to Payment Page
                    </a>
                </div>
                <script>
                    let pollCount = 0;
                    const maxPolls = 60; // Poll for up to 5 minutes (60 * 5 seconds)
                    const pollInterval = 5000; // 5 seconds

                    function checkPaymentStatus() {
                        pollCount++;
                        
                        if (pollCount >= maxPolls) {
                            // Stop polling after max attempts
                            document.querySelector('.animate-spin').classList.remove('animate-spin');
                            document.querySelector('h2').textContent = 'Payment Status Unknown';
                            document.querySelector('p').textContent = 'Please check your payment status manually or contact support.';
                            return;
                        }

                        // Reload page to check updated status
                        fetch(window.location.href, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                            cache: 'no-cache'
                        })
                        .then(response => response.text())
                        .then(html => {
                            // Check if the response contains "Payment Confirmed" (paid status)
                            if (html.includes('Payment Confirmed') || html.includes('is Paid')) {
                                // Payment confirmed - reload page to show success
                                window.location.reload();
                            } else {
                                // Still processing - continue polling
                                setTimeout(checkPaymentStatus, pollInterval);
                            }
                        })
                        .catch(error => {
                            console.error('Error checking payment status:', error);
                            // Continue polling on error
                            setTimeout(checkPaymentStatus, pollInterval);
                        });
                    }

                    // Start polling after initial delay
                    setTimeout(checkPaymentStatus, pollInterval);
                </script>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
