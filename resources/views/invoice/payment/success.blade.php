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
                    <h1 class="text-2xl font-bold text-white">Awaiting Payment Confirmation</h1>
                </div>
                <div class="px-6 py-8 text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                        <svg class="w-10 h-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center animate-pulse">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Please Confirm Payment on Your Phone</h2>
                    <p class="text-gray-600 mb-4">An M-Pesa payment request has been sent to your phone.</p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-medium text-yellow-800 mb-2">📱 Next Steps:</p>
                        <ol class="text-sm text-yellow-700 list-decimal list-inside space-y-1">
                            <li>Check your phone for the M-Pesa prompt</li>
                            <li>Enter your M-Pesa PIN to confirm</li>
                            <li>This page will automatically update once payment is confirmed</li>
                        </ol>
                    </div>
                    @if($outstanding > 0)
                    <p class="text-sm text-gray-500 mb-4">Amount: <span class="font-semibold">KES {{ number_format($outstanding, 2) }}</span></p>
                    @endif
                    <p class="text-sm text-gray-500 mb-6">Waiting for payment confirmation... This page will update automatically.</p>
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
                    // Get checkout_request_id from URL parameter
                    const urlParams = new URLSearchParams(window.location.search);
                    const checkoutRequestId = urlParams.get('checkout_request_id');
                    
                    if (!checkoutRequestId) {
                        console.warn('No checkout_request_id found in URL');
                    }

                    let pollCount = 0;
                    const maxPolls = 120; // Poll for up to 2 minutes (120 * 1 second)
                    const pollInterval = 1000; // Poll every 1 second for faster detection (like subscription flow)
                    let poll = null;
                    let paymentConfirmed = false;

                    function checkPaymentStatus() {
                        if (paymentConfirmed || !checkoutRequestId) {
                            return; // Stop if already confirmed or no checkout ID
                        }

                        pollCount++;
                        
                        if (pollCount >= maxPolls) {
                            // Stop polling after max attempts
                            if (poll) clearInterval(poll);
                            const heading = document.querySelector('h2');
                            if (heading) heading.textContent = 'Payment Status Unknown';
                            const message = document.querySelector('.text-gray-600');
                            if (message) message.textContent = 'Please check your payment status manually or contact support.';
                            return;
                        }

                        // Check payment status via dedicated status endpoint (like subscription flow)
                        const statusUrl = '/pay/{{ $invoice->id }}/{{ $invoice->payment_token }}/status?checkout_request_id=' + encodeURIComponent(checkoutRequestId) + '&_=' + Date.now();
                        
                        fetch(statusUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-cache'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Payment status check:', data);
                            
                            // Check if payment is completed (like subscription flow)
                            if (data.status === 'success' || (data.status === 'processing' && data.invoice_paid === true)) {
                                // Payment confirmed - stop polling and redirect immediately
                                if (poll) clearInterval(poll);
                                paymentConfirmed = true;
                                
                                // Redirect immediately to show success (like subscription flow)
                                window.location.reload();
                            } else if (data.status === 'failed') {
                                // Payment failed
                                if (poll) clearInterval(poll);
                                paymentConfirmed = true;
                                const heading = document.querySelector('h2');
                                if (heading) heading.textContent = 'Payment Failed';
                                const message = document.querySelector('.text-gray-600');
                                if (message) message.textContent = 'Payment failed. Please try again.';
                            } else if (pollCount >= maxPolls) {
                                // Timeout
                                if (poll) clearInterval(poll);
                                const heading = document.querySelector('h2');
                                if (heading) heading.textContent = 'Payment Status Unknown';
                                const message = document.querySelector('.text-gray-600');
                                if (message) message.textContent = 'Please check your payment status manually or contact support.';
                            }
                            // If still pending, continue polling (handled by setInterval)
                        })
                        .catch(error => {
                            console.error('Error checking payment status:', error);
                            // Continue polling on error (might be temporary network issue)
                            if (pollCount >= maxPolls) {
                                if (poll) clearInterval(poll);
                            }
                        });
                    }

                    // Start polling immediately if checkout_request_id is available (like subscription flow)
                    if (checkoutRequestId) {
                        checkPaymentStatus();
                        // Then continue polling at intervals
                        poll = setInterval(checkPaymentStatus, pollInterval);
                    } else {
                        // Fallback: use the old method if no checkout_request_id
                        setTimeout(() => {
                            if (!paymentConfirmed) {
                                checkPaymentStatus();
                            }
                        }, 2000);
                    }
                </script>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

