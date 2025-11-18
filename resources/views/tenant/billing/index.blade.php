@extends('layouts.tenant')

@section('title', 'Billing & Subscriptions')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Billing & Subscriptions</h1>

    <!-- Current Subscription -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Subscription</h2>
        
        @if($subscription)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600">Plan</p>
                <p class="text-lg font-semibold text-gray-900 capitalize">{{ $subscription->plan }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Status</p>
                <p class="text-lg font-semibold capitalize">
                    <span class="px-2 py-1 rounded text-sm 
                        @if($subscription->status === 'active') bg-green-100 text-green-800
                        @elseif($subscription->status === 'trial') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ $subscription->status }}
                    </span>
                </p>
            </div>
            @if($subscription->trial_ends_at)
            <div>
                <p class="text-sm text-gray-600">Trial Ends</p>
                <p class="text-lg font-semibold text-gray-900">{{ $subscription->trial_ends_at->format('M d, Y') }}</p>
            </div>
            @endif
            @if($subscription->next_billing_at)
            <div>
                <p class="text-sm text-gray-600">Next Billing</p>
                <p class="text-lg font-semibold text-gray-900">{{ $subscription->next_billing_at->format('M d, Y') }}</p>
            </div>
            @endif
        </div>

        <!-- Change Plan -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Plan</h3>
            <form method="POST" action="{{ route('tenant.billing.update-plan') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @foreach($plans as $planKey => $plan)
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ $subscription->plan === $planKey ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                        <input type="radio" name="plan" value="{{ $planKey }}" {{ $subscription->plan === $planKey ? 'checked' : '' }} class="mr-3">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $plan['name'] }}</div>
                            <div class="text-sm text-gray-600">{{ $plan['price_display'] }}/month</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                        Update Plan
                    </button>
                </div>
            </form>
        </div>
        @else
        <p class="text-gray-600">No active subscription found.</p>
        @endif
    </div>

    <!-- Payment Methods -->
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Payment Methods</h2>
            <button onclick="showAddPaymentMethodModal()" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                Add Payment Method
            </button>
        </div>

        @if($paymentMethods->count() > 0)
        <div class="space-y-4">
            @foreach($paymentMethods as $method)
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-4">
                    <div>
                        @if($method->type === 'mpesa')
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        @elseif(in_array($method->type, ['debit_card', 'credit_card']))
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        @elseif($method->type === 'paypal')
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900">{{ $method->getDisplayName() }}</div>
                        <div class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $method->type) }}</div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @if($method->is_default)
                    <span class="px-2 py-1 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">Default</span>
                    @else
                    <form method="POST" action="{{ route('tenant.billing.payment-methods.set-default', $method) }}">
                        @csrf
                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">Set as Default</button>
                    </form>
                    @endif
                    @if($paymentMethods->count() > 1)
                    <form method="POST" action="{{ route('tenant.billing.payment-methods.destroy', $method) }}" onsubmit="return confirm('Are you sure you want to remove this payment method?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">Remove</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-600">No payment methods added yet.</p>
        @endif
    </div>
</div>

<!-- Add Payment Method Modal -->
<div id="add-payment-method-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Add Payment Method</h3>
                <button onclick="hideAddPaymentMethodModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('tenant.billing.payment-methods.store') }}" id="add-payment-method-form">
                @csrf
                <input type="hidden" name="type" id="payment_method_type">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectPaymentType('mpesa')" class="p-3 border-2 border-gray-200 rounded-lg hover:border-indigo-500 text-sm">
                            M-Pesa
                        </button>
                        <button type="button" onclick="selectPaymentType('debit_card')" class="p-3 border-2 border-gray-200 rounded-lg hover:border-indigo-500 text-sm">
                            Debit Card
                        </button>
                        <button type="button" onclick="selectPaymentType('credit_card')" class="p-3 border-2 border-gray-200 rounded-lg hover:border-indigo-500 text-sm">
                            Credit Card
                        </button>
                        <button type="button" onclick="selectPaymentType('paypal')" class="p-3 border-2 border-gray-200 rounded-lg hover:border-indigo-500 text-sm">
                            PayPal
                        </button>
                    </div>
                </div>

                <div id="payment-details-container" class="hidden">
                    <!-- M-Pesa -->
                    <div id="mpesa-details" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Friendly Name (Optional)</label>
                            <input type="text" name="name" class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="text" name="mpesa_phone" id="modal_mpesa_phone" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="254712345678">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                            <input type="text" name="mpesa_name" id="modal_mpesa_name" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Demo M-Pesa Account">
                        </div>
                    </div>

                    <!-- Debit/Credit Card -->
                    <div id="card-details" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Friendly Name (Optional)</label>
                            <input type="text" name="name" class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Card Number *</label>
                            <input type="text" name="card_number" id="modal_card_number" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="4111 1111 1111 1111" maxlength="19">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name *</label>
                            <input type="text" name="cardholder_name" id="modal_cardholder_name" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Demo User">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Month *</label>
                                <input type="text" name="expiry_month" id="modal_expiry_month" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="12" maxlength="2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Year *</label>
                                <input type="text" name="expiry_year" id="modal_expiry_year" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="2025" maxlength="4">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">CVV *</label>
                                <input type="text" name="cvv" id="modal_cvv" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>

                    <!-- PayPal -->
                    <div id="paypal-details" class="hidden space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Friendly Name (Optional)</label>
                            <input type="text" name="name" class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Email *</label>
                            <input type="email" name="paypal_email" id="modal_paypal_email" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="demo@paypal.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="paypal_password" id="modal_paypal_password" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="demo123">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_default" value="1" class="mr-2">
                            <span class="text-sm text-gray-700">Set as default payment method</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideAddPaymentMethodModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white" style="background-color: var(--brand-primary);">
                            Add Payment Method
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Demo payment details for prefilling
const demoPaymentDetails = @json($isTestMode ? $demoPaymentDetails : []);

function showAddPaymentMethodModal() {
    document.getElementById('add-payment-method-modal').classList.remove('hidden');
}

function hideAddPaymentMethodModal() {
    document.getElementById('add-payment-method-modal').classList.add('hidden');
    document.getElementById('payment-details-container').classList.add('hidden');
    document.querySelectorAll('[id$="-details"]').forEach(el => el.classList.add('hidden'));
    document.getElementById('payment_method_type').value = '';
}

function selectPaymentType(type) {
    document.getElementById('payment_method_type').value = type;
    document.getElementById('payment-details-container').classList.remove('hidden');
    
    // Hide all detail forms
    document.querySelectorAll('[id$="-details"]').forEach(el => el.classList.add('hidden'));
    
    // Show selected form
    if (type === 'mpesa') {
        document.getElementById('mpesa-details').classList.remove('hidden');
        // Prefill if demo mode
        if (demoPaymentDetails.mpesa) {
            document.getElementById('modal_mpesa_phone').value = demoPaymentDetails.mpesa.phone_number || '';
            document.getElementById('modal_mpesa_name').value = demoPaymentDetails.mpesa.name || '';
        }
    } else if (type === 'debit_card' || type === 'credit_card') {
        document.getElementById('card-details').classList.remove('hidden');
        // Prefill if demo mode
        const details = demoPaymentDetails[type];
        if (details) {
            document.getElementById('modal_card_number').value = details.card_number || '';
            document.getElementById('modal_cardholder_name').value = details.cardholder_name || '';
            document.getElementById('modal_expiry_month').value = details.expiry_month || '';
            document.getElementById('modal_expiry_year').value = details.expiry_year || '';
            document.getElementById('modal_cvv').value = details.cvv || '';
        }
    } else if (type === 'paypal') {
        document.getElementById('paypal-details').classList.remove('hidden');
        // Prefill if demo mode
        if (demoPaymentDetails.paypal) {
            document.getElementById('modal_paypal_email').value = demoPaymentDetails.paypal.email || '';
            document.getElementById('modal_paypal_password').value = demoPaymentDetails.paypal.password || '';
        }
    }
}

// Format card number input
document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.getElementById('modal_card_number');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
});
</script>
@endsection

