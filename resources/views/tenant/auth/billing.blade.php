@extends('layouts.tenant')

@section('title', 'Choose Your Plan - LegitBooks')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Choose Your Subscription Plan</h1>
        <p class="text-lg text-gray-600">Select a plan and payment method to complete your registration</p>
    </div>

    <form method="POST" action="{{ route('tenant.auth.billing.submit') }}" id="billing-form">
        @csrf

        <!-- Subscription Plans -->
        <div class="mb-12">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Select Plan</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($plans as $planKey => $plan)
                <div class="bg-white rounded-lg shadow-lg p-6 border-2 {{ old('plan') == $planKey ? 'border-indigo-500' : 'border-gray-200' }} cursor-pointer hover:shadow-xl transition-shadow"
                     onclick="selectPlan('{{ $planKey }}')">
                    <input type="radio" name="plan" value="{{ $planKey }}" id="plan_{{ $planKey }}" 
                           class="hidden" {{ old('plan') == $planKey ? 'checked' : '' }} required>
                    <label for="plan_{{ $planKey }}" class="cursor-pointer">
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan['name'] }}</h3>
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-gray-900">{{ $plan['price_display'] }}</span>
                            @if($plan['price'] > 0)
                            <span class="text-gray-600">/month</span>
                            @endif
                        </div>
                        <ul class="space-y-2 mb-4">
                            @foreach($plan['features'] as $feature)
                            <li class="flex items-start text-sm text-gray-600">
                                <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </label>
                </div>
                @endforeach
            </div>
            @error('plan')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Payment Gateway Selection -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Select Payment Method</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- M-Pesa -->
                <div class="bg-white rounded-lg shadow p-6 border-2 {{ old('payment_gateway') == 'mpesa' ? 'border-indigo-500' : 'border-gray-200' }} cursor-pointer hover:shadow-lg transition-shadow"
                     onclick="selectGateway('mpesa')">
                    <input type="radio" name="payment_gateway" value="mpesa" id="gateway_mpesa" 
                           class="hidden" {{ old('payment_gateway') == 'mpesa' ? 'checked' : '' }} required>
                    <label for="gateway_mpesa" class="cursor-pointer flex flex-col items-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">M-Pesa</span>
                        <span class="text-sm text-gray-600 mt-1">Mobile Money</span>
                    </label>
                </div>

                <!-- Debit Card -->
                <div class="bg-white rounded-lg shadow p-6 border-2 {{ old('payment_gateway') == 'debit_card' ? 'border-indigo-500' : 'border-gray-200' }} cursor-pointer hover:shadow-lg transition-shadow"
                     onclick="selectGateway('debit_card')">
                    <input type="radio" name="payment_gateway" value="debit_card" id="gateway_debit_card" 
                           class="hidden" {{ old('payment_gateway') == 'debit_card' ? 'checked' : '' }} required>
                    <label for="gateway_debit_card" class="cursor-pointer flex flex-col items-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Debit Card</span>
                        <span class="text-sm text-gray-600 mt-1">Visa/Mastercard</span>
                    </label>
                </div>

                <!-- Credit Card -->
                <div class="bg-white rounded-lg shadow p-6 border-2 {{ old('payment_gateway') == 'credit_card' ? 'border-indigo-500' : 'border-gray-200' }} cursor-pointer hover:shadow-lg transition-shadow"
                     onclick="selectGateway('credit_card')">
                    <input type="radio" name="payment_gateway" value="credit_card" id="gateway_credit_card" 
                           class="hidden" {{ old('payment_gateway') == 'credit_card' ? 'checked' : '' }} required>
                    <label for="gateway_credit_card" class="cursor-pointer flex flex-col items-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Credit Card</span>
                        <span class="text-sm text-gray-600 mt-1">Visa/Mastercard</span>
                    </label>
                </div>

                <!-- PayPal -->
                <div class="bg-white rounded-lg shadow p-6 border-2 {{ old('payment_gateway') == 'paypal' ? 'border-indigo-500' : 'border-gray-200' }} cursor-pointer hover:shadow-lg transition-shadow"
                     onclick="selectGateway('paypal')">
                    <input type="radio" name="payment_gateway" value="paypal" id="gateway_paypal" 
                           class="hidden" {{ old('payment_gateway') == 'paypal' ? 'checked' : '' }} required>
                    <label for="gateway_paypal" class="cursor-pointer flex flex-col items-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">PayPal</span>
                        <span class="text-sm text-gray-600 mt-1">PayPal Account</span>
                    </label>
                </div>
            </div>
            @error('payment_gateway')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Payment Details Form (shown when gateway is selected) -->
        <div id="payment-details-form" class="mb-8 hidden">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl mx-auto">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Payment Details</h3>
                
                <!-- M-Pesa Form -->
                <div id="mpesa-form" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label for="mpesa_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="text" name="mpesa_phone" id="mpesa_phone" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="254712345678">
                        </div>
                        <div>
                            <label for="mpesa_name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" name="mpesa_name" id="mpesa_name" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Demo M-Pesa Account">
                        </div>
                    </div>
                </div>

                <!-- Debit Card Form -->
                <div id="debit_card-form" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label for="debit_card_number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
                            <input type="text" name="debit_card_number" id="debit_card_number" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="4111 1111 1111 1111" maxlength="19">
                        </div>
                        <div>
                            <label for="debit_cardholder_name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                            <input type="text" name="debit_cardholder_name" id="debit_cardholder_name" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Demo User">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="debit_expiry_month" class="block text-sm font-medium text-gray-700 mb-2">Expiry Month</label>
                                <input type="text" name="debit_expiry_month" id="debit_expiry_month" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="12" maxlength="2">
                            </div>
                            <div>
                                <label for="debit_expiry_year" class="block text-sm font-medium text-gray-700 mb-2">Expiry Year</label>
                                <input type="text" name="debit_expiry_year" id="debit_expiry_year" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="2025" maxlength="4">
                            </div>
                            <div>
                                <label for="debit_cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                <input type="text" name="debit_cvv" id="debit_cvv" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Card Form -->
                <div id="credit_card-form" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label for="credit_card_number" class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
                            <input type="text" name="credit_card_number" id="credit_card_number" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="5555 5555 5555 4444" maxlength="19">
                        </div>
                        <div>
                            <label for="credit_cardholder_name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                            <input type="text" name="credit_cardholder_name" id="credit_cardholder_name" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Demo User">
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="credit_expiry_month" class="block text-sm font-medium text-gray-700 mb-2">Expiry Month</label>
                                <input type="text" name="credit_expiry_month" id="credit_expiry_month" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="12" maxlength="2">
                            </div>
                            <div>
                                <label for="credit_expiry_year" class="block text-sm font-medium text-gray-700 mb-2">Expiry Year</label>
                                <input type="text" name="credit_expiry_year" id="credit_expiry_year" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="2025" maxlength="4">
                            </div>
                            <div>
                                <label for="credit_cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                <input type="text" name="credit_cvv" id="credit_cvv" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PayPal Form -->
                <div id="paypal-form" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label for="paypal_email" class="block text-sm font-medium text-gray-700 mb-2">PayPal Email</label>
                            <input type="email" name="paypal_email" id="paypal_email" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="demo@paypal.com">
                        </div>
                        <div>
                            <label for="paypal_password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" name="paypal_password" id="paypal_password" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="demo123">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-center">
            <button type="submit" id="submit-btn" class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white opacity-50 cursor-not-allowed" style="background-color: var(--brand-primary);" disabled>
                Complete Registration
            </button>
        </div>
    </form>
</div>

<script>
// Demo payment details
const demoPaymentDetails = @json($isTestMode ? $demoPaymentDetails : []);

function selectPlan(planKey) {
    // Uncheck all plan radios
    document.querySelectorAll('input[name="plan"]').forEach(radio => {
        radio.checked = false;
        radio.closest('.border-2').classList.remove('border-indigo-500');
        radio.closest('.border-2').classList.add('border-gray-200');
    });
    
    // Check selected plan
    const selectedRadio = document.getElementById('plan_' + planKey);
    selectedRadio.checked = true;
    selectedRadio.closest('.border-2').classList.remove('border-gray-200');
    selectedRadio.closest('.border-2').classList.add('border-indigo-500');
    
    checkFormComplete();
}

function selectGateway(gateway) {
    // Uncheck all gateway radios
    document.querySelectorAll('input[name="payment_gateway"]').forEach(radio => {
        radio.checked = false;
        radio.closest('.border-2').classList.remove('border-indigo-500');
        radio.closest('.border-2').classList.add('border-gray-200');
    });
    
    // Check selected gateway
    const selectedRadio = document.getElementById('gateway_' + gateway);
    selectedRadio.checked = true;
    selectedRadio.closest('.border-2').classList.remove('border-gray-200');
    selectedRadio.closest('.border-2').classList.add('border-indigo-500');
    
    // Show payment details form
    showPaymentForm(gateway);
    checkFormComplete();
}

function showPaymentForm(gateway) {
    // Hide all payment forms
    document.querySelectorAll('[id$="-form"]').forEach(form => {
        form.classList.add('hidden');
    });
    
    // Show selected payment form
    const form = document.getElementById(gateway + '-form');
    if (form) {
        form.classList.remove('hidden');
        document.getElementById('payment-details-form').classList.remove('hidden');
        
        // Prefill with demo data if in test mode
        if (demoPaymentDetails[gateway]) {
            prefillPaymentForm(gateway, demoPaymentDetails[gateway]);
        }
    }
}

function prefillPaymentForm(gateway, details) {
    if (gateway === 'mpesa') {
        document.getElementById('mpesa_phone').value = details.phone_number || '';
        document.getElementById('mpesa_name').value = details.name || '';
    } else if (gateway === 'debit_card') {
        document.getElementById('debit_card_number').value = details.card_number || '';
        document.getElementById('debit_cardholder_name').value = details.cardholder_name || '';
        document.getElementById('debit_expiry_month').value = details.expiry_month || '';
        document.getElementById('debit_expiry_year').value = details.expiry_year || '';
        document.getElementById('debit_cvv').value = details.cvv || '';
    } else if (gateway === 'credit_card') {
        document.getElementById('credit_card_number').value = details.card_number || '';
        document.getElementById('credit_cardholder_name').value = details.cardholder_name || '';
        document.getElementById('credit_expiry_month').value = details.expiry_month || '';
        document.getElementById('credit_expiry_year').value = details.expiry_year || '';
        document.getElementById('credit_cvv').value = details.cvv || '';
    } else if (gateway === 'paypal') {
        document.getElementById('paypal_email').value = details.email || '';
        document.getElementById('paypal_password').value = details.password || '';
    }
}

function checkFormComplete() {
    const planSelected = document.querySelector('input[name="plan"]:checked');
    const gatewaySelected = document.querySelector('input[name="payment_gateway"]:checked');
    const submitBtn = document.getElementById('submit-btn');
    
    if (planSelected && gatewaySelected) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Format card number input
document.addEventListener('DOMContentLoaded', function() {
    const cardInputs = ['debit_card_number', 'credit_card_number'];
    cardInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            });
        }
    });
    
    // Check if form is already filled (from old() values)
    const gatewaySelected = document.querySelector('input[name="payment_gateway"]:checked');
    if (gatewaySelected) {
        showPaymentForm(gatewaySelected.value);
    }
    
    checkFormComplete();
});
</script>
@endsection

