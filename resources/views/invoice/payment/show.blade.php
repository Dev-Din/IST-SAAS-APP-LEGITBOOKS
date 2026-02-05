<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gray-800 px-6 py-4">
                    <h1 class="text-2xl font-bold text-white">Invoice {{ $invoice->invoice_number }}</h1>
                    <p class="text-gray-300 text-sm mt-1">{{ $invoice->tenant->name }}</p>
                </div>

                <!-- Invoice Details -->
                <div class="px-6 py-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h2>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Invoice Date:</span>
                                <span class="font-medium">{{ $invoice->invoice_date->format('d/m/Y') }}</span>
                            </div>
                            @if($invoice->due_date)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Due Date:</span>
                                <span class="font-medium">{{ $invoice->due_date->format('d/m/Y') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">KES {{ number_format($invoice->subtotal, 2) }}</span>
                            </div>
                            @if($invoice->tax_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium">KES {{ number_format($invoice->tax_amount, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between border-t border-gray-200 pt-2 mt-2">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-gray-900">KES {{ number_format($invoice->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h2>
                        
                        <!-- M-Pesa Payment -->
                        <div class="border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">M-Pesa</h3>
                                        <p class="text-sm text-gray-600">Pay via M-Pesa STK Push</p>
                                    </div>
                                </div>
                            </div>
                            <form id="mpesa-form" class="space-y-3">
                                @csrf
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" 
                                           placeholder="254712345678" 
                                           pattern="^254\d{9}$"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <p class="text-xs text-gray-500 mt-1">Format: 254712345678</p>
                                </div>
                                <button type="submit" 
                                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    Pay with M-Pesa
                                </button>
                            </form>
                            <div id="mpesa-message" class="mt-2 text-sm hidden"></div>
                        </div>

                        <!-- Card Payment (Placeholder) -->
                        <div class="border border-gray-200 rounded-lg p-4 mb-4 opacity-60">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">Debit/Credit Card</h3>
                                        <p class="text-sm text-gray-600">Coming soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PayPal Payment (Placeholder) -->
                        <div class="border border-gray-200 rounded-lg p-4 opacity-60">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900">PayPal</h3>
                                        <p class="text-sm text-gray-600">Coming soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('mpesa-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const button = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('mpesa-message');
            const phoneInput = document.getElementById('phone_number');
            
            // Validate phone number format
            const phone = phoneInput.value.trim();
            if (!/^254\d{9}$/.test(phone)) {
                messageDiv.textContent = 'Please enter a valid phone number in format 254712345678';
                messageDiv.className = 'mt-2 text-sm text-red-600';
                messageDiv.classList.remove('hidden');
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Processing...';
            messageDiv.classList.add('hidden');
            
            try {
                const response = await fetch('{{ route('invoice.pay.mpesa', [$invoice->id, $invoice->payment_token]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        phone_number: phone,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect immediately to confirmation page with checkout request ID
                    const checkoutId = data.checkoutRequestID || '';
                    window.location.href = '{{ route('invoice.pay.success', [$invoice->id, $invoice->payment_token]) }}?checkout_request_id=' + encodeURIComponent(checkoutId);
                } else {
                    messageDiv.textContent = data.error || 'Payment failed. Please try again.';
                    messageDiv.className = 'mt-2 text-sm text-red-600';
                    messageDiv.classList.remove('hidden');
                    button.disabled = false;
                    button.textContent = 'Pay with M-Pesa';
                }
            } catch (error) {
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.className = 'mt-2 text-sm text-red-600';
                messageDiv.classList.remove('hidden');
                button.disabled = false;
                button.textContent = 'Pay with M-Pesa';
            }
        });
    </script>
</body>
</html>

