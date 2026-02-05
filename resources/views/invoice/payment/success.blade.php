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
                @include('invoice.payment._receipt-body')
                @else
                <div id="payment-dialog" class="px-6 py-12 text-center">
                    <div id="payment-loading" class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-gray-700 font-medium">Confirming your payment…</p>
                        <p class="text-sm text-gray-600 mt-1">Please complete the payment on your phone. This page will reload automatically until your payment is confirmed.</p>
                        @if($outstanding > 0)
                        <p class="text-sm text-gray-500 mt-2">Amount: KES {{ number_format($outstanding, 2) }}</p>
                        @endif
                        <p id="reload-countdown" class="text-xs text-gray-400 mt-3">Checking again in <span id="countdown-sec">3</span> seconds…</p>
                    </div>
                    <div id="payment-result" class="hidden">
                        <p id="payment-result-message" class="text-gray-700 mb-6"></p>
                        <div class="flex flex-wrap gap-3 justify-center">
                            <a href="{{ route('invoice.pay', [$invoice->id, $invoice->payment_token]) }}" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">Return to Payment Page</a>
                            <button type="button" id="payment-refresh-btn" class="hidden inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">Refresh</button>
                        </div>
                    </div>
                </div>
                {{-- Full page reload every 3s so server runs sync and redirects to receipt when payment is received. --}}
                <script>
                    (function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const checkoutRequestId = urlParams.get('checkout_request_id');
                        const reloadDelayMs = 3000;
                        const maxReloads = 40;
                        const storageKey = 'pay_reload_count_{{ $invoice->id }}_{{ $invoice->payment_token }}';

                        const loadingEl = document.getElementById('payment-loading');
                        const resultEl = document.getElementById('payment-result');
                        const resultMessageEl = document.getElementById('payment-result-message');
                        const refreshBtn = document.getElementById('payment-refresh-btn');
                        const countdownEl = document.getElementById('countdown-sec');

                        function showResult(message, showRefresh) {
                            if (loadingEl) loadingEl.classList.add('hidden');
                            if (resultEl) resultEl.classList.remove('hidden');
                            if (resultMessageEl) resultMessageEl.textContent = message;
                            if (refreshBtn) refreshBtn.classList.toggle('hidden', !showRefresh);
                        }

                        if (!checkoutRequestId) {
                            showResult('Return to the payment page to try again.', false);
                        } else {
                            let count = parseInt(sessionStorage.getItem(storageKey) || '0', 10);
                            if (count >= maxReloads) {
                                sessionStorage.removeItem(storageKey);
                                showResult('Payment is taking longer than expected. Click Refresh to check again or return to the payment page.', true);
                                if (refreshBtn) refreshBtn.addEventListener('click', function() { sessionStorage.setItem(storageKey, '0'); window.location.reload(); });
                            } else {
                                sessionStorage.setItem(storageKey, String(count + 1));
                                var sec = Math.ceil(reloadDelayMs / 1000);
                                if (countdownEl) {
                                    var t = setInterval(function() {
                                        sec--;
                                        if (countdownEl) countdownEl.textContent = sec > 0 ? sec : 0;
                                        if (sec <= 0) clearInterval(t);
                                    }, 1000);
                                }
                                setTimeout(function() {
                                    window.location.reload();
                                }, reloadDelayMs);
                                if (refreshBtn) refreshBtn.addEventListener('click', function() { window.location.reload(); });
                            }
                        }
                    })();
                </script>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

