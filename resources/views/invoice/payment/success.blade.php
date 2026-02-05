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
                        <p class="text-gray-700 font-medium">Processing payment…</p>
                        <p class="text-sm text-gray-600 mt-1">Please check your phone and enter PIN. You will be redirected to the <strong>Payment Successful</strong> page when payment is confirmed.</p>
                        @if($outstanding > 0)
                        <p class="text-sm text-gray-500 mt-2">Amount: KES {{ number_format($outstanding, 2) }}</p>
                        @endif
                        <p id="payment-ref" class="text-xs text-gray-400 mt-2 font-mono"></p>
                        <p id="poll-status" class="text-xs text-gray-400 mt-1">Checking payment status…</p>
                    </div>
                    <div id="payment-result" class="hidden">
                        <p id="payment-result-message" class="text-gray-700 mb-6"></p>
                        <div class="flex flex-wrap gap-3 justify-center">
                            <a href="{{ route('invoice.pay', [$invoice->id, $invoice->payment_token]) }}" class="inline-block bg-gray-800 text-white px-6 py-2 rounded-md hover:bg-gray-900">Return to Payment Page</a>
                            <button type="button" id="payment-refresh-btn" class="hidden inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">Refresh</button>
                        </div>
                    </div>
                </div>
                {{-- Payment status polling: GET /pay/{id}/{token}/status every 2s so status loads as soon as payment is received. Max 90 attempts (~3 min). --}}
                <script>
                    (function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const checkoutRequestId = urlParams.get('checkout_request_id');
                        const pollIntervalMs = 2000;  // 2 seconds so Payment Successful page loads as soon as payment is received
                        const maxPolls = 90;          // ~3 minutes total
                        let pollCount = 0;
                        let pollTimer = null;
                        let paymentConfirmed = false;

                        const loadingEl = document.getElementById('payment-loading');
                        const resultEl = document.getElementById('payment-result');
                        const resultMessageEl = document.getElementById('payment-result-message');
                        const refreshBtn = document.getElementById('payment-refresh-btn');
                        const paymentRefEl = document.getElementById('payment-ref');
                        const pollStatusEl = document.getElementById('poll-status');

                        if (paymentRefEl && checkoutRequestId) {
                            paymentRefEl.textContent = 'Request ID: ' + checkoutRequestId.substring(0, 16) + (checkoutRequestId.length > 16 ? '…' : '');
                        }

                        /** Stop polling, show final message. State: pending -> success|failed|timeout. */
                        function showResult(message, showRefresh) {
                            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                            paymentConfirmed = true;
                            loadingEl.classList.add('hidden');
                            resultEl.classList.remove('hidden');
                            resultMessageEl.textContent = message;
                            refreshBtn.classList.toggle('hidden', !showRefresh);
                        }

                        /** Clear polling interval to prevent memory leaks on unmount or navigation. */
                        function clearPolling() {
                            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                        }

                        /** Poll backend for payment status. Backend may query Daraja if still pending. On success redirect to receipt; on failed redirect to failed page; on timeout show message. */
                        function checkPaymentStatus() {
                            if (paymentConfirmed || !checkoutRequestId) return;
                            pollCount++;
                            if (pollStatusEl) pollStatusEl.textContent = 'Checking payment status… (attempt ' + pollCount + '/' + maxPolls + ')';
                            if (pollCount > maxPolls) {
                                clearPolling();
                                showResult('Payment is taking longer than expected. You can refresh to check again or return to the payment page.', true);
                                return;
                            }
                            const statusUrl = '{{ route('invoice.pay.status', [$invoice->id, $invoice->payment_token]) }}?checkout_request_id=' + encodeURIComponent(checkoutRequestId) + '&_=' + Date.now();
                            fetch(statusUrl, {
                                method: 'GET',
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                                cache: 'no-cache'
                            })
                            .then(function(r) {
                                if (r.ok) return r.json();
                                if (r.status === 404) {
                                    clearPolling();
                                    showResult('Payment session not found. If you have already paid, click Refresh to check.', true);
                                    return Promise.reject(new Error('Payment not found'));
                                }
                                return Promise.reject(new Error('Network error'));
                            })
                            .then(function(data) {
                                if (!data) return;
                                if (data.invoice_paid === true || data.status === 'success' || (data.status === 'processing' && data.invoice_paid === true)) {
                                    clearPolling();
                                    paymentConfirmed = true;
                                    window.location.href = '{{ route('invoice.pay.receipt', [$invoice->id, $invoice->payment_token]) }}';
                                } else if (data.status === 'failed') {
                                    clearPolling();
                                    paymentConfirmed = true;
                                    window.location.href = '{{ route('invoice.pay.failed', [$invoice->id, $invoice->payment_token]) }}';
                                } else if (pollCount >= maxPolls) {
                                    clearPolling();
                                    showResult('Payment is taking longer than expected. You can refresh to check again or return to the payment page.', true);
                                }
                            })
                            .catch(function(err) {
                                if (err && err.message !== 'Payment not found') {
                                    console.error('Payment status check error:', err);
                                }
                            });
                        }

                        if (refreshBtn) {
                            refreshBtn.addEventListener('click', function() { window.location.reload(); });
                        }

                        window.addEventListener('beforeunload', clearPolling);
                        window.addEventListener('pagehide', clearPolling);

                        if (checkoutRequestId) {
                            checkPaymentStatus();
                            pollTimer = setInterval(checkPaymentStatus, pollIntervalMs);
                        } else {
                            showResult('Return to the payment page to try again.', false);
                        }
                    })();
                </script>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

