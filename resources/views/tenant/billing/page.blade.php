@extends('layouts.tenant')

@section('title', 'Upgrade Your Plan')

@section('content')
@php
    $isTestMode = $isTestMode ?? false;
    $demoPaymentDetails = $demoPaymentDetails ?? [];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header with Logo -->
    <div class="mb-8 text-center">
        <img src="/mnt/data/A_logo_design_is_displayed_in_this_digital_vector_.png" alt="LegitBooks Logo" class="h-16 w-auto mx-auto mb-4" onerror="this.style.display='none'">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Upgrade Your Plan</h1>
        <p class="text-gray-600">Choose the plan that best fits your business needs</p>
    </div>

    @if(session('info'))
        <div class="mb-6 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded" role="alert">
            {{ session('info') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <!-- Plan Comparison Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @foreach($plans as $planKey => $plan)
        <div class="bg-white border-2 rounded-lg p-6 shadow-sm {{ $subscription && $subscription->plan === $planKey ? 'border-brand-primary' : 'border-gray-200' }} {{ $planKey === 'plan_free' ? 'opacity-75' : '' }}">
            <div class="text-center mb-4">
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan['name'] }}</h3>
                <div class="text-3xl font-bold text-gray-900 mb-1">{{ $plan['price_display'] }}</div>
                @if($plan['price'] > 0)
                    <div class="text-sm text-gray-500">per month</div>
                @endif
            </div>

            @if($subscription && $subscription->plan === $planKey)
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">Current Plan</span>
                </div>
            @endif

            <ul class="space-y-2 mb-6">
                @foreach($plan['features'] as $feature)
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-gray-700">{{ $feature }}</span>
                </li>
                @endforeach
            </ul>

            @if($planKey === 'plan_free')
                <button
                    disabled
                    class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed"
                >
                    Current Plan
                </button>
            @else
                <!-- Payment Method Selection for this plan -->
                <div class="plan-checkout-{{ $planKey }}" data-plan-key="{{ $planKey }}" data-plan-name="{{ $plan['name'] }}" data-plan-price="{{ $plan['price'] }}">
                    <form method="POST" action="{{ route('tenant.billing.upgrade') }}" class="plan-upgrade-form">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $planKey }}">

                        <!-- Payment Method Selection -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Payment Method *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:opacity-80 transition-all payment-method-option" style="border-color: #e5e7eb;" onmouseover="this.style.borderColor='var(--brand-primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e5e7eb'">
                                    <input type="radio" name="payment_gateway" value="mpesa" class="mr-2 payment-gateway-radio" onchange="selectPaymentMethod('{{ $planKey }}', 'mpesa')" required>
                                    <div class="text-xs">
                                        <div class="font-semibold text-gray-900">M-Pesa</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:opacity-80 transition-all payment-method-option" style="border-color: #e5e7eb;" onmouseover="this.style.borderColor='var(--brand-primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e5e7eb'">
                                    <input type="radio" name="payment_gateway" value="debit_card" class="mr-2 payment-gateway-radio" onchange="selectPaymentMethod('{{ $planKey }}', 'debit_card')" required>
                                    <div class="text-xs">
                                        <div class="font-semibold text-gray-900">Debit Card</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:opacity-80 transition-all payment-method-option" style="border-color: #e5e7eb;" onmouseover="this.style.borderColor='var(--brand-primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e5e7eb'">
                                    <input type="radio" name="payment_gateway" value="credit_card" class="mr-2 payment-gateway-radio" onchange="selectPaymentMethod('{{ $planKey }}', 'credit_card')" required>
                                    <div class="text-xs">
                                        <div class="font-semibold text-gray-900">Credit Card</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:opacity-80 transition-all payment-method-option" style="border-color: #e5e7eb;" onmouseover="this.style.borderColor='var(--brand-primary)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e5e7eb'">
                                    <input type="radio" name="payment_gateway" value="paypal" class="mr-2 payment-gateway-radio" onchange="selectPaymentMethod('{{ $planKey }}', 'paypal')" required>
                                    <div class="text-xs">
                                        <div class="font-semibold text-gray-900">PayPal</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Details Forms -->
                        <div id="payment-details-{{ $planKey }}" class="hidden mb-4">
                            <!-- M-Pesa Form -->
                            <div id="mpesa-form-{{ $planKey }}" class="hidden bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 text-sm mb-2">M-Pesa Payment Details</h4>
                                <div>
                                    <label for="mpesa_phone_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                    <input type="text" name="mpesa_phone" id="mpesa_phone_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="254712345678" required>
                                </div>
                                <div>
                                    <label for="mpesa_name_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input type="text" name="mpesa_name" id="mpesa_name_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="Demo M-Pesa Account" required>
                                </div>
                            </div>

                            <!-- Debit Card Form -->
                            <div id="debit_card-form-{{ $planKey }}" class="hidden bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 text-sm mb-2">Debit Card Payment Details</h4>
                                <div>
                                    <label for="debit_card_number_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Card Number *</label>
                                    <input type="text" name="card_number" id="debit_card_number_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="4111 1111 1111 1111" maxlength="19" required>
                                </div>
                                <div>
                                    <label for="debit_cardholder_name_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name *</label>
                                    <input type="text" name="cardholder_name" id="debit_cardholder_name_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="Demo User" required>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label for="debit_expiry_month_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                                        <input type="text" name="expiry_month" id="debit_expiry_month_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="12" maxlength="2" required>
                                    </div>
                                    <div>
                                        <label for="debit_expiry_year_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                                        <input type="text" name="expiry_year" id="debit_expiry_year_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="2025" maxlength="4" required>
                                    </div>
                                    <div>
                                        <label for="debit_cvv_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                                        <input type="text" name="cvv" id="debit_cvv_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="123" maxlength="3" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Credit Card Form -->
                            <div id="credit_card-form-{{ $planKey }}" class="hidden bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 text-sm mb-2">Credit Card Payment Details</h4>
                                <div>
                                    <label for="credit_card_number_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Card Number *</label>
                                    <input type="text" name="card_number" id="credit_card_number_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="5555 5555 5555 4444" maxlength="19" required>
                                </div>
                                <div>
                                    <label for="credit_cardholder_name_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name *</label>
                                    <input type="text" name="cardholder_name" id="credit_cardholder_name_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="Demo User" required>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label for="credit_expiry_month_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                                        <input type="text" name="expiry_month" id="credit_expiry_month_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="12" maxlength="2" required>
                                    </div>
                                    <div>
                                        <label for="credit_expiry_year_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                                        <input type="text" name="expiry_year" id="credit_expiry_year_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="2025" maxlength="4" required>
                                    </div>
                                    <div>
                                        <label for="credit_cvv_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                                        <input type="text" name="cvv" id="credit_cvv_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="123" maxlength="3" required>
                                    </div>
                                </div>
                            </div>

                            <!-- PayPal Form -->
                            <div id="paypal-form-{{ $planKey }}" class="hidden bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 text-sm mb-2">PayPal Payment Details</h4>
                                <div>
                                    <label for="paypal_email_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">PayPal Email *</label>
                                    <input type="email" name="paypal_email" id="paypal_email_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="demo@paypal.com" required>
                                </div>
                                <div>
                                    <label for="paypal_password_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                    <input type="password" name="paypal_password" id="paypal_password_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="demo123" required>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submit-btn-{{ $planKey }}" 
                                class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:opacity-90 transition-opacity opacity-50 cursor-not-allowed"
                                style="background-color: var(--brand-primary);" disabled>
                            Complete Payment
                        </button>
                    </form>
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>

<script>
// Demo payment details for prefilling
const demoPaymentDetails = @json(isset($isTestMode) && $isTestMode && isset($demoPaymentDetails) ? $demoPaymentDetails : []);

function selectPaymentMethod(planKey, gateway) {
    const planContainer = document.querySelector('.plan-checkout-' + planKey);
    const paymentDetailsContainer = document.getElementById('payment-details-' + planKey);
    const submitBtn = document.getElementById('submit-btn-' + planKey);
    
    // Show payment details container
    if (paymentDetailsContainer) {
        paymentDetailsContainer.classList.remove('hidden');
    }
    
    // Hide all forms for this plan
    const forms = ['mpesa', 'debit_card', 'credit_card', 'paypal'];
    forms.forEach(formType => {
        const formElement = document.getElementById(formType + '-form-' + planKey);
        if (formElement) {
            formElement.classList.add('hidden');
        }
    });
    
    // Show selected form
    const selectedForm = document.getElementById(gateway + '-form-' + planKey);
    if (selectedForm) {
        selectedForm.classList.remove('hidden');
    }
    
    // Enable submit button
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    // Update border color for selected payment method
    const planForm = planContainer?.querySelector('.plan-upgrade-form');
    if (planForm) {
        planForm.querySelectorAll('.payment-method-option').forEach(option => {
            option.style.borderColor = '#e5e7eb';
        });
        const selectedOption = planForm.querySelector('input[value="' + gateway + '"]')?.closest('.payment-method-option');
        if (selectedOption) {
            selectedOption.style.borderColor = 'var(--brand-primary)';
        }
    }
    
    // Prefill demo data if available
    if (typeof demoPaymentDetails !== 'undefined' && demoPaymentDetails[gateway]) {
        prefillPaymentForm(planKey, gateway, demoPaymentDetails[gateway]);
    }
}

function prefillPaymentForm(planKey, gateway, details) {
    if (gateway === 'mpesa') {
        const phoneInput = document.getElementById('mpesa_phone_' + planKey);
        const nameInput = document.getElementById('mpesa_name_' + planKey);
        if (phoneInput) phoneInput.value = details.phone_number || '';
        if (nameInput) nameInput.value = details.name || '';
    } else if (gateway === 'debit_card') {
        const cardInput = document.getElementById('debit_card_number_' + planKey);
        const nameInput = document.getElementById('debit_cardholder_name_' + planKey);
        const monthInput = document.getElementById('debit_expiry_month_' + planKey);
        const yearInput = document.getElementById('debit_expiry_year_' + planKey);
        const cvvInput = document.getElementById('debit_cvv_' + planKey);
        if (cardInput) cardInput.value = details.card_number || '';
        if (nameInput) nameInput.value = details.cardholder_name || '';
        if (monthInput) monthInput.value = details.expiry_month || '';
        if (yearInput) yearInput.value = details.expiry_year || '';
        if (cvvInput) cvvInput.value = details.cvv || '';
    } else if (gateway === 'credit_card') {
        const cardInput = document.getElementById('credit_card_number_' + planKey);
        const nameInput = document.getElementById('credit_cardholder_name_' + planKey);
        const monthInput = document.getElementById('credit_expiry_month_' + planKey);
        const yearInput = document.getElementById('credit_expiry_year_' + planKey);
        const cvvInput = document.getElementById('credit_cvv_' + planKey);
        if (cardInput) cardInput.value = details.card_number || '';
        if (nameInput) nameInput.value = details.cardholder_name || '';
        if (monthInput) monthInput.value = details.expiry_month || '';
        if (yearInput) yearInput.value = details.expiry_year || '';
        if (cvvInput) cvvInput.value = details.cvv || '';
    } else if (gateway === 'paypal') {
        const emailInput = document.getElementById('paypal_email_' + planKey);
        const passwordInput = document.getElementById('paypal_password_' + planKey);
        if (emailInput) emailInput.value = details.email || '';
        if (passwordInput) passwordInput.value = details.password || '';
    }
}

// Format card number input
document.addEventListener('DOMContentLoaded', function() {
    // Add card number formatting for all card inputs
    document.querySelectorAll('input[id$="_card_number_' + '"]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    });
    
    // Format all debit and credit card inputs
    ['debit', 'credit'].forEach(cardType => {
        const cardInputs = document.querySelectorAll('input[id^="' + cardType + '_card_number_"]');
        cardInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            });
        });
    });
});
</script>
@endsection
