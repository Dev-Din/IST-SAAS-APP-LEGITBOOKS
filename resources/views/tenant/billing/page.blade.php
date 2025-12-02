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

    @if($errors->any())
        <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Processing Payment Card (Hidden by default) -->
    <div id="processing-payment-card" class="mb-8 bg-blue-50 border-2 border-blue-300 rounded-lg p-6 shadow-md hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-blue-900 mb-1">Processing Payment</h3>
                    <p class="text-sm text-blue-700" id="processing-message">Waiting for payment confirmation...</p>
                    <p class="text-xs text-blue-600 mt-2" id="processing-details">Please complete the payment on your phone.</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium text-blue-900" id="payment-status-badge">Pending</div>
            </div>
        </div>
        <div class="mt-4">
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div id="processing-progress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    </div>

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
                    <form method="POST" action="{{ route('tenant.billing.upgrade') }}" class="plan-upgrade-form" data-plan="{{ $planKey }}">
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
                                           placeholder="254712345678" 
                                           data-payment-field="mpesa"
                                           disabled>
                                </div>
                            </div>

                            <!-- Debit Card Form -->
                            <div id="debit_card-form-{{ $planKey }}" class="hidden bg-gray-50 rounded-lg p-4 space-y-3">
                                <h4 class="font-semibold text-gray-900 text-sm mb-2">Debit Card Payment Details</h4>
                                <div>
                                    <label for="debit_card_number_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Card Number *</label>
                                    <input type="text" name="card_number" id="debit_card_number_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="4111 1111 1111 1111" maxlength="19"
                                           data-payment-field="card"
                                           disabled>
                                </div>
                                <div>
                                    <label for="debit_cardholder_name_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name *</label>
                                    <input type="text" name="cardholder_name" id="debit_cardholder_name_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="Demo User"
                                           data-payment-field="card"
                                           disabled>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label for="debit_expiry_month_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                                        <input type="text" name="expiry_month" id="debit_expiry_month_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="12" maxlength="2"
                                               data-payment-field="card"
                                               disabled>
                                    </div>
                                    <div>
                                        <label for="debit_expiry_year_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                                        <input type="text" name="expiry_year" id="debit_expiry_year_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="2025" maxlength="4"
                                               data-payment-field="card"
                                               disabled>
                                    </div>
                                    <div>
                                        <label for="debit_cvv_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                                        <input type="text" name="cvv" id="debit_cvv_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="123" maxlength="3"
                                               data-payment-field="card"
                                               disabled>
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
                                           placeholder="5555 5555 5555 4444" maxlength="19"
                                           data-payment-field="card"
                                           disabled>
                                </div>
                                <div>
                                    <label for="credit_cardholder_name_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name *</label>
                                    <input type="text" name="cardholder_name" id="credit_cardholder_name_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="Demo User"
                                           data-payment-field="card"
                                           disabled>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label for="credit_expiry_month_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Month *</label>
                                        <input type="text" name="expiry_month" id="credit_expiry_month_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="12" maxlength="2"
                                               data-payment-field="card"
                                               disabled>
                                    </div>
                                    <div>
                                        <label for="credit_expiry_year_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Year *</label>
                                        <input type="text" name="expiry_year" id="credit_expiry_year_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="2025" maxlength="4"
                                               data-payment-field="card"
                                               disabled>
                                    </div>
                                    <div>
                                        <label for="credit_cvv_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">CVV *</label>
                                        <input type="text" name="cvv" id="credit_cvv_{{ $planKey }}" 
                                               class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                               placeholder="123" maxlength="3"
                                               data-payment-field="card"
                                               disabled>
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
                                           placeholder="demo@paypal.com"
                                           data-payment-field="paypal"
                                           disabled>
                                </div>
                                <div>
                                    <label for="paypal_password_{{ $planKey }}" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                    <input type="password" name="paypal_password" id="paypal_password_{{ $planKey }}" 
                                           class="w-full h-12 px-4 rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-primary"
                                           placeholder="demo123"
                                           data-payment-field="paypal"
                                           disabled>
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
    
    // Hide all forms for this plan and disable all inputs
    const forms = ['mpesa', 'debit_card', 'credit_card', 'paypal'];
    forms.forEach(formType => {
        const formElement = document.getElementById(formType + '-form-' + planKey);
        if (formElement) {
            formElement.classList.add('hidden');
            // Disable all inputs in this form
            formElement.querySelectorAll('input').forEach(input => {
                input.disabled = true;
                input.removeAttribute('required');
            });
        }
    });
    
    // Show selected form and enable inputs
    const selectedForm = document.getElementById(gateway + '-form-' + planKey);
    if (selectedForm) {
        selectedForm.classList.remove('hidden');
        // Enable all inputs in the selected form
        selectedForm.querySelectorAll('input').forEach(input => {
            input.disabled = false;
            input.setAttribute('required', 'required');
        });
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
        if (phoneInput) phoneInput.value = details.phone_number || '';
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

// Prepare form submission - ensure all fields are enabled
function prepareFormSubmission(planKey, form) {
    const selectedGateway = form.querySelector('input[name="payment_gateway"]:checked')?.value;
    
    if (!selectedGateway) {
        alert('Please select a payment method');
        return false;
    }
    
    // Enable fields based on selected payment method
    if (selectedGateway === 'mpesa') {
        const phoneInput = document.getElementById('mpesa_phone_' + planKey);
        if (phoneInput) {
            phoneInput.removeAttribute('disabled');
            phoneInput.disabled = false;
        }
    } else if (selectedGateway === 'debit_card' || selectedGateway === 'credit_card') {
        const cardNumberInput = document.getElementById(selectedGateway + '_card_number_' + planKey);
        const cardholderInput = document.getElementById(selectedGateway + '_cardholder_name_' + planKey);
        const expiryMonthInput = document.getElementById(selectedGateway + '_expiry_month_' + planKey);
        const expiryYearInput = document.getElementById(selectedGateway + '_expiry_year_' + planKey);
        const cvvInput = document.getElementById(selectedGateway + '_cvv_' + planKey);
        
        [cardNumberInput, cardholderInput, expiryMonthInput, expiryYearInput, cvvInput].forEach(input => {
            if (input) {
                input.removeAttribute('disabled');
                input.disabled = false;
            }
        });
    } else if (selectedGateway === 'paypal') {
        const emailInput = document.getElementById('paypal_email_' + planKey);
        const passwordInput = document.getElementById('paypal_password_' + planKey);
        if (emailInput) {
            emailInput.removeAttribute('disabled');
            emailInput.disabled = false;
        }
        if (passwordInput) {
            passwordInput.removeAttribute('disabled');
            passwordInput.disabled = false;
        }
    }
    
    return true;
}

// Prefill M-Pesa phone number if demo data available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof demoPaymentDetails !== 'undefined' && demoPaymentDetails.mpesa) {
        document.querySelectorAll('input[id^="mpesa_phone_"]').forEach(input => {
            if (!input.value && demoPaymentDetails.mpesa.phone_number) {
                input.value = demoPaymentDetails.mpesa.phone_number;
            }
        });
    }
    
    // Handle form submission with AJAX for M-Pesa
    document.querySelectorAll('.plan-upgrade-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const formData = new FormData(this);
            const paymentGateway = formData.get('payment_gateway');
            
            // For M-Pesa, use dedicated STK initiation endpoint
            if (paymentGateway === 'mpesa') {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                const planKey = this.dataset.plan || this.closest('[class*="plan-checkout-"]')?.dataset.planKey;
                
                // Get phone number
                const phoneInput = document.getElementById('mpesa_phone_' + planKey);
                const phone = phoneInput ? phoneInput.value.trim() : '';
                
                // Validate phone
                if (!phone || !/^2547\d{8}$/.test(phone)) {
                    alert('Please enter a valid phone number in format 2547XXXXXXXX (e.g., 254712345678)');
                    return;
                }
                
                // Disable button and show loading
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
                
                // Call STK initiation endpoint
                fetch('{{ route("tenant.billing.mpesa.initiate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify({
                        plan: formData.get('plan'),
                        phone: phone
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        // Show processing payment card
                        showProcessingCard(data.message || 'STK Push sent. Enter your M-Pesa PIN.');
                        
                        // Start polling for payment status
                        if (data.checkoutRequestID) {
                            pollPaymentStatus(data.checkoutRequestID);
                        } else {
                            // Fallback: redirect to billing page
                            setTimeout(() => {
                                window.location.href = '{{ route("tenant.billing.page") }}?message=' + encodeURIComponent(data.message || 'STK Push sent. Please complete payment on your phone.');
                            }, 3000);
                        }
                    } else {
                        const errorMsg = data.error || 'Failed to initiate payment. Please try again.';
                        alert(errorMsg);
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
            }
            // For other payment methods, submit normally
        });
    });

    // Function to show processing payment card
    function showProcessingCard(message) {
        const card = document.getElementById('processing-payment-card');
        const messageEl = document.getElementById('processing-message');
        const detailsEl = document.getElementById('processing-details');
        
        if (card && messageEl && detailsEl) {
            messageEl.textContent = message;
            detailsEl.textContent = 'Please complete the payment on your phone. This page will automatically redirect once payment is confirmed.';
            card.classList.remove('hidden');
            
            // Scroll to card
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Function to update processing card status
    function updateProcessingCard(status, message, progress) {
        const statusBadge = document.getElementById('payment-status-badge');
        const messageEl = document.getElementById('processing-message');
        const progressBar = document.getElementById('processing-progress');
        
        if (statusBadge) {
            statusBadge.textContent = status;
            if (status === 'Completed') {
                statusBadge.classList.add('text-green-600');
            }
        }
        
        if (messageEl && message) {
            messageEl.textContent = message;
        }
        
        if (progressBar && progress !== undefined) {
            progressBar.style.width = progress + '%';
        }
    }

    // Function to poll payment status
    function pollPaymentStatus(checkoutRequestId) {
        let pollCount = 0;
        const maxPolls = 120; // Poll for up to 2 minutes (120 * 1 second)
        const pollInterval = 1000; // Poll every 1 second for faster detection
        let lastStatus = 'pending';
        let poll = null;

        // Start polling immediately (don't wait for first interval)
        const checkStatus = () => {
            pollCount++;
            
            // Update progress bar (show progress based on time, not just count)
            const progress = Math.min((pollCount / maxPolls) * 100, 95);
            if (lastStatus === 'pending') {
                updateProcessingCard('Processing', 'Waiting for payment confirmation...', progress);
            }

            fetch('{{ route("tenant.billing.mpesa.status", ["checkoutRequestID" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', checkoutRequestId) + '?_=' + Date.now(), {
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
                console.log('Payment status check:', data); // Debug log
                
                // Check if payment is completed
                if (data.status === 'completed' || data.status === 'success') {
                    if (poll) clearInterval(poll);
                    updateProcessingCard('Completed', 'Payment received! Activating subscription...', 100);
                    
                    // Payment completed - redirect immediately if subscription is active
                    if (data.subscription_active) {
                        // Update card to show success
                        updateProcessingCard('Success', 'Payment successful! Redirecting...', 100);
                        
                        // Redirect to dashboard with success message
                        setTimeout(() => {
                            window.location.href = '{{ route("tenant.dashboard") }}?paid=1';
                        }, 500);
                    } else {
                        // Payment completed but subscription not active yet - check again quickly
                        updateProcessingCard('Processing', 'Payment received! Activating subscription...', 95);
                        lastStatus = 'processing';
                        // Check again in 500ms (faster)
                        setTimeout(() => {
                            checkStatus();
                        }, 500);
                    }
                } else if (data.status === 'failed') {
                    if (poll) clearInterval(poll);
                    updateProcessingCard('Failed', 'Payment failed. Please try again.', 100);
                    setTimeout(() => {
                        window.location.href = '{{ route("tenant.billing.page") }}';
                    }, 3000);
                } else if (pollCount >= maxPolls) {
                    // Timeout - stop polling
                    if (poll) clearInterval(poll);
                    updateProcessingCard('Timeout', 'Payment verification timeout. Please check your payment status manually.', 100);
                    setTimeout(() => {
                        window.location.href = '{{ route("tenant.billing.page") }}';
                    }, 3000);
                } else {
                    // Still pending - update progress
                    if (data.status && data.status !== lastStatus) {
                        lastStatus = data.status;
                    }
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
                // Continue polling on error (might be temporary network issue)
                if (pollCount >= maxPolls) {
                    if (poll) clearInterval(poll);
                    updateProcessingCard('Error', 'Unable to verify payment status. Please check manually.', 100);
                    setTimeout(() => {
                        window.location.href = '{{ route("tenant.billing.page") }}';
                    }, 3000);
                }
            });
        };

        // Start checking immediately
        checkStatus();
        
        // Then continue polling at intervals
        poll = setInterval(checkStatus, pollInterval);
    }
});
</script>
@endsection
