@extends('layouts.tenant')

@section('title', 'Processing Payment')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Spinner -->
        <div class="mb-6 flex justify-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600"></div>
        </div>

        <!-- Message -->
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Waiting for payment confirmation...</h2>
        <p class="text-gray-600 mb-2">Complete the prompt on your phone</p>
        <p class="text-sm text-gray-500 mb-6">Phone: {{ $phone }}</p>

        <!-- Payment Details -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Plan:</span>
                <span class="font-semibold text-gray-900">{{ ucfirst($plan) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Amount:</span>
                <span class="font-semibold text-gray-900">KSh {{ number_format($amount, 2) }}</span>
            </div>
        </div>

        <!-- Status Message -->
        <div id="status-message" class="text-sm text-gray-500 mb-4">
            Processing your payment...
        </div>

        <!-- Error Message (hidden by default) -->
        <div id="error-message" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
            <p id="error-text"></p>
            <button onclick="retryPayment()" class="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                Retry Payment
            </button>
        </div>

        <!-- Manual Check Button (shown after timeout or always available) -->
        <div id="manual-check" class="hidden mt-4">
            <button onclick="checkStatus()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                I paid — check now
            </button>
        </div>
    </div>
</div>

<script>
const clientToken = '{{ $client_token }}';
const plan = '{{ $plan }}';
const pollUrl = '{{ route("tenant.checkout.mpesa-status", ["plan" => $plan, "token" => $client_token]) }}';
let pollCount = 0;
const maxPolls = 150; // 5 minutes (150 * 2s = 300 seconds)
const pollInterval = 2000; // 2 seconds (aggressive polling for dev)
let pollIntervalId = null;
let timeoutReached = false;
const timeoutMs = 5 * 60 * 1000; // 5 minutes in milliseconds
let startTime = Date.now();

function startPolling() {
    // Initial check
    checkStatus();
    
    // Poll every 2 seconds
    pollIntervalId = setInterval(() => {
        pollCount++;
        
        // Check if 5 minutes have elapsed
        const elapsed = Date.now() - startTime;
        if (elapsed >= timeoutMs && !timeoutReached) {
            timeoutReached = true;
            clearInterval(pollIntervalId);
            document.getElementById('status-message').textContent = 'Payment verification timeout. Please check manually if you have completed the payment.';
            document.getElementById('manual-check').classList.remove('hidden');
            return;
        }
        
        // Continue polling if not timed out
        if (!timeoutReached) {
            checkStatus();
        }
    }, pollInterval);
}

function checkStatus() {
    fetch(pollUrl + '?_=' + Date.now(), {
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
        if (data.status === 'success') {
            // Stop polling
            if (pollIntervalId) {
                clearInterval(pollIntervalId);
            }

            // Show success message
            document.getElementById('status-message').textContent = data.message || 'Payment successful! Redirecting...';
            document.getElementById('status-message').className = 'text-sm text-green-600 font-semibold mb-4';

            // Show success alert
            alert('Payment successful! Redirecting to dashboard...');
            
            // Redirect after brief delay
            setTimeout(() => {
                const redirectUrl = data.redirect || '{{ route("tenant.dashboard") }}?payment=success';
                // Use replaceState to avoid back-button confusion
                window.history.replaceState(null, '', redirectUrl);
                window.location.href = redirectUrl;
            }, 1500);
        } else if (data.status === 'failed') {
            // Stop polling
            if (pollIntervalId) {
                clearInterval(pollIntervalId);
            }

            // Show error
            document.getElementById('error-message').classList.remove('hidden');
            document.getElementById('error-text').textContent = data.error || 'Payment failed. Please try again.';
            document.getElementById('status-message').textContent = 'Payment failed';
        } else if (data.status === 'pending') {
            // Continue polling (timeout handled in setInterval)
            const elapsed = Date.now() - startTime;
            if (elapsed >= timeoutMs && !timeoutReached) {
                timeoutReached = true;
                if (pollIntervalId) {
                    clearInterval(pollIntervalId);
                }
                document.getElementById('status-message').textContent = 'Payment verification timeout. Please check manually if you have completed the payment.';
                document.getElementById('manual-check').classList.remove('hidden');
            }
        }
    })
    .catch(error => {
        console.error('Polling error:', error);
        // Continue polling on error (might be temporary network issue)
        if (pollCount >= maxPolls && !timeoutReached) {
            timeoutReached = true;
            if (pollIntervalId) {
                clearInterval(pollIntervalId);
            }
            document.getElementById('status-message').textContent = 'Unable to verify payment status. Please check manually.';
            document.getElementById('manual-check').classList.remove('hidden');
        }
    });
}

function retryPayment() {
    // Redirect back to billing page
    window.location.href = '{{ route("tenant.billing.page") }}';
}

// Start polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    startPolling();
});
</script>
@endsection

