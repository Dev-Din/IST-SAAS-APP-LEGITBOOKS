<?php

namespace App\Http\Controllers\Tenant;

use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Services\MpesaStkService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;
        $paymentMethods = PaymentMethod::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $plans = [
            'free' => ['name' => 'Free', 'price' => 0, 'price_display' => 'Free'],
            'starter' => ['name' => 'Starter', 'price' => 2500, 'price_display' => 'KSh 2,500'],
            'business' => ['name' => 'Business', 'price' => 5000, 'price_display' => 'KSh 5,000'],
            'enterprise' => ['name' => 'Enterprise', 'price' => 0, 'price_display' => 'Custom'],
        ];

        $isTestMode = PaymentHelper::isTestMode();
        $demoPaymentDetails = [];
        if ($isTestMode) {
            foreach (['mpesa', 'debit_card', 'credit_card', 'paypal'] as $gateway) {
                $demoPaymentDetails[$gateway] = PaymentHelper::getDemoPaymentDetails($gateway);
            }
        }

        return view('tenant.billing.index', compact('subscription', 'paymentMethods', 'plans', 'tenant', 'isTestMode', 'demoPaymentDetails'));
    }

    public function updatePlan(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;

        $validated = $request->validate([
            'plan' => 'required|in:free,starter,business,enterprise',
        ]);

        if (! $subscription) {
            return back()->withErrors(['plan' => 'No active subscription found.']);
        }

        // Plan pricing hierarchy
        $planHierarchy = ['free' => 0, 'starter' => 1, 'business' => 2, 'enterprise' => 3];
        $currentPlanLevel = $planHierarchy[$subscription->plan] ?? 0;
        $newPlanLevel = $planHierarchy[$validated['plan']] ?? 0;

        // If upgrading to a paid plan (higher level), require payment
        $paidPlans = ['starter', 'business', 'enterprise'];
        $isUpgradeToPaid = in_array($validated['plan'], $paidPlans) && $newPlanLevel > $currentPlanLevel;

        if ($isUpgradeToPaid) {
            // Redirect to upgrade page which requires payment
            return redirect()->route('tenant.billing.page')
                ->with('info', 'Please complete payment to upgrade to the '.ucfirst($validated['plan']).' plan.');
        }

        // Allow downgrades or same plan changes without payment
        try {
            DB::beginTransaction();

            // Update subscription (downgrade or same plan)
            $subscription->update([
                'plan' => $validated['plan'],
                // Keep existing payment_gateway and settings
            ]);

            DB::commit();

            return back()->with('success', 'Subscription plan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Plan update failed: '.$e->getMessage(), [
                'exception' => $e,
                'tenant_id' => $tenant->id,
            ]);

            return back()->withErrors(['plan' => 'An error occurred while updating your plan. Please try again.']);
        }
    }

    public function storePaymentMethod(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $validated = $request->validate([
            'type' => 'required|in:mpesa,debit_card,credit_card,paypal',
            'name' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            // M-Pesa fields
            'mpesa_phone' => 'required_if:type,mpesa|nullable|string',
            'mpesa_name' => 'required_if:type,mpesa|nullable|string',
            // Card fields
            'card_number' => 'required_if:type,debit_card,credit_card|nullable|string',
            'cardholder_name' => 'required_if:type,debit_card,credit_card|nullable|string',
            'expiry_month' => 'required_if:type,debit_card,credit_card|nullable|string',
            'expiry_year' => 'required_if:type,debit_card,credit_card|nullable|string',
            'cvv' => 'required_if:type,debit_card,credit_card|nullable|string',
            // PayPal fields
            'paypal_email' => 'required_if:type,paypal|nullable|email',
            'paypal_password' => 'required_if:type,paypal|nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Prepare payment details based on type
            $details = [];
            if ($validated['type'] === 'mpesa') {
                $details = [
                    'phone_number' => $validated['mpesa_phone'],
                    'name' => $validated['mpesa_name'],
                ];
            } elseif (in_array($validated['type'], ['debit_card', 'credit_card'])) {
                $details = [
                    'card_number' => str_replace(' ', '', $validated['card_number']),
                    'cardholder_name' => $validated['cardholder_name'],
                    'expiry_month' => $validated['expiry_month'],
                    'expiry_year' => $validated['expiry_year'],
                    'cvv' => $validated['cvv'],
                ];
            } elseif ($validated['type'] === 'paypal') {
                $details = [
                    'email' => $validated['paypal_email'],
                    'password' => $validated['paypal_password'],
                ];
            }

            // If this is set as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                PaymentMethod::where('tenant_id', $tenant->id)
                    ->update(['is_default' => false]);
            }

            PaymentMethod::create([
                'tenant_id' => $tenant->id,
                'type' => $validated['type'],
                'name' => $validated['name'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => true,
                'details' => $details,
            ]);

            DB::commit();

            return back()->with('success', 'Payment method added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to add payment method. Please try again.']);
        }
    }

    public function setDefaultPaymentMethod(PaymentMethod $paymentMethod, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        if ($paymentMethod->tenant_id !== $tenant->id) {
            abort(403);
        }

        DB::transaction(function () use ($tenant, $paymentMethod) {
            PaymentMethod::where('tenant_id', $tenant->id)
                ->update(['is_default' => false]);

            $paymentMethod->update(['is_default' => true]);
        });

        return back()->with('success', 'Default payment method updated.');
    }

    public function destroyPaymentMethod(PaymentMethod $paymentMethod, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        if ($paymentMethod->tenant_id !== $tenant->id) {
            abort(403);
        }

        // Don't allow deleting if it's the only payment method
        $count = PaymentMethod::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($count <= 1) {
            return back()->withErrors(['error' => 'Cannot delete the last payment method.']);
        }

        $paymentMethod->update(['is_active' => false]);

        return back()->with('success', 'Payment method removed successfully.');
    }

    /**
     * Show the billing/upgrade page
     */
    public function page(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;

        $plans = [
            'plan_free' => [
                'name' => 'Free',
                'price' => 0,
                'price_display' => 'Free',
                'features' => ['50 invoices/month', '1 user', 'Basic reporting', 'Email support'],
            ],
            'starter' => [
                'name' => 'Starter',
                'price' => 2500,
                'price_display' => 'KSh 2,500',
                'features' => ['Unlimited invoices', 'Up to 3 users', 'Advanced reporting', 'M-Pesa integration', 'Priority support'],
            ],
            'business' => [
                'name' => 'Business',
                'price' => 5000,
                'price_display' => 'KSh 5,000',
                'features' => ['Unlimited invoices', 'Up to 10 users', 'Custom reports', 'CSV import/export', '24/7 support'],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 0,
                'price_display' => 'Custom',
                'features' => ['Unlimited everything', 'Unlimited users', 'White-label options', 'API access', 'Dedicated account manager'],
            ],
        ];

        // Get demo payment details for prefilling forms
        $isTestMode = PaymentHelper::isTestMode();
        $demoPaymentDetails = [];
        if ($isTestMode) {
            foreach (['mpesa', 'debit_card', 'credit_card', 'paypal'] as $gateway) {
                $demoPaymentDetails[$gateway] = PaymentHelper::getDemoPaymentDetails($gateway);
            }
        }

        return view('tenant.billing.page', compact('tenant', 'subscription', 'plans', 'isTestMode', 'demoPaymentDetails'));
    }

    /**
     * Initiate M-Pesa STK Push for subscription payment
     * POST /tenant/billing/mpesa/initiate
     */
    public function initiateMpesaPayment(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;

        $validated = $request->validate([
            'plan' => 'required|in:starter,business,enterprise',
            'phone' => 'required|string|regex:/^2547\d{8}$/',
        ], [
            'phone.regex' => 'Phone number must be in format 2547XXXXXXXX (e.g., 254712345678)',
        ]);

        // Get plan pricing
        $planPrices = [
            'starter' => 2500,
            'business' => 5000,
            'enterprise' => 0, // Custom pricing
        ];
        $amount = $planPrices[$validated['plan']] ?? 0;

        if ($amount <= 0) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid plan selected',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $mpesaService = app(MpesaStkService::class);

            if (! $mpesaService->isConfigured()) {
                DB::rollBack();

                return response()->json([
                    'ok' => false,
                    'error' => 'M-Pesa is not configured. Please contact support.',
                ], 400);
            }

            // Get or create M-Pesa account
            $mpesaAccount = Account::where('tenant_id', $tenant->id)
                ->where('type', 'mpesa')
                ->first();

            if (! $mpesaAccount) {
                $cashAccount = ChartOfAccount::where('tenant_id', $tenant->id)
                    ->where('code', '1400')
                    ->first();

                if ($cashAccount) {
                    $mpesaAccount = Account::create([
                        'tenant_id' => $tenant->id,
                        'name' => 'M-Pesa',
                        'type' => 'mpesa',
                        'chart_of_account_id' => $cashAccount->id,
                        'is_active' => true,
                    ]);
                }
            }

            // Generate payment number
            $paymentNumber = 'SUB-'.date('Ymd').'-'.str_pad(
                Payment::where('tenant_id', $tenant->id)->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Create payment record (pending)
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'payment_number' => $paymentNumber,
                'payment_date' => now()->toDateString(),
                'account_id' => $mpesaAccount->id ?? null,
                'amount' => $amount,
                'payment_method' => 'mpesa',
                'phone' => $validated['phone'],
                'transaction_status' => 'pending',
            ]);

            // Initiate STK Push
            $stkResult = $mpesaService->initiateSTKPush([
                'phone_number' => $validated['phone'],
                'amount' => $amount,
                'account_reference' => 'SUB-'.$subscription->id,
                'transaction_desc' => 'Subscription payment for '.ucfirst($validated['plan']).' plan',
            ]);

            if (! $stkResult['success']) {
                DB::rollBack();

                return response()->json([
                    'ok' => false,
                    'error' => $stkResult['error'] ?? 'Failed to initiate M-Pesa payment. Please try again.',
                ], 400);
            }

            // Update payment with STK push details
            $payment->update([
                'checkout_request_id' => $stkResult['checkoutRequestID'],
                'merchant_request_id' => $stkResult['merchantRequestID'],
            ]);

            // Update subscription (pending payment)
            $subscription->update([
                'plan' => $validated['plan'],
                'status' => 'pending', // Will be activated on payment confirmation
                'payment_gateway' => 'mpesa',
                'settings' => array_merge($subscription->settings ?? [], [
                    'phone_number' => $validated['phone'],
                ]),
            ]);

            DB::commit();

            Log::info('M-Pesa STK Push initiated for subscription', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'plan' => $validated['plan'],
                'checkout_request_id' => $stkResult['checkoutRequestID'],
            ]);

            return response()->json([
                'ok' => true,
                'checkoutRequestID' => $stkResult['checkoutRequestID'],
                'message' => 'STK Push sent. Enter your M-Pesa PIN.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('M-Pesa STK initiation failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'An error occurred while processing your payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Check payment status by checkout request ID (Polling endpoint)
     * GET /tenant/billing/mpesa/status/{checkoutRequestID}
     */
    public function checkMpesaStatus(string $checkoutRequestID, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();

        $payment = Payment::where('checkout_request_id', $checkoutRequestID)
            ->where('tenant_id', $tenant->id)
            ->with('subscription')
            ->first();

        if (! $payment) {
            return response()->json([
                'status' => 'not_found',
                'error' => 'Payment not found',
            ], 404);
        }

        // If payment is still pending, query Daraja API to get latest status
        // Only query if payment has been pending for at least 15 seconds (give user time to enter PIN)
        $secondsSinceCreated = $payment->created_at->diffInSeconds(now());
        if ($payment->transaction_status === 'pending' && $secondsSinceCreated >= 15) {
            $mpesaService = app(MpesaStkService::class);
            $queryResult = $mpesaService->querySTKPushStatus($checkoutRequestID);

            if ($queryResult['success'] && isset($queryResult['is_paid']) && $queryResult['is_paid']) {
                // Payment was successful - process it
                try {
                    DB::beginTransaction();

                    $payment->update([
                        'transaction_status' => 'completed',
                        'reference' => $queryResult['checkout_request_id'] ?? $checkoutRequestID,
                    ]);

                    // Activate subscription
                    if ($payment->subscription_id) {
                        $subscription = $payment->subscription;
                        $subscription->update([
                            'status' => 'active',
                            'started_at' => now(),
                            'ends_at' => now()->addMonth(),
                            'next_billing_at' => now()->addMonth(),
                        ]);
                    }

                    DB::commit();

                    Log::info('Payment status updated via polling query', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutRequestID,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to update payment status from polling', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $payment->refresh();
            } elseif ($queryResult['success'] && isset($queryResult['result_code'])) {
                // Check if result_code indicates actual failure (not just pending)
                // Known failure codes: 1032 (cancelled), 1037 (timeout), 2001 (insufficient funds), etc.
                $failureCodes = ['1032', '2001', '2002', '2003', '2004', '2005', '2006', '2007', '2008', '2009'];
                $resultCode = (string) $queryResult['result_code'];
                $resultDesc = strtolower($queryResult['result_desc'] ?? '');

                // Check if it's a known failure code OR if description indicates failure
                $isFailure = in_array($resultCode, $failureCodes) ||
                             str_contains($resultDesc, 'cancelled') ||
                             str_contains($resultDesc, 'insufficient') ||
                             str_contains($resultDesc, 'failed') ||
                             str_contains($resultDesc, 'declined');

                if ($isFailure) {
                    // Actual failure - mark as failed
                    $payment->update([
                        'transaction_status' => 'failed',
                    ]);
                    $payment->refresh();

                    Log::info('Payment marked as failed from Daraja query', [
                        'payment_id' => $payment->id,
                        'result_code' => $resultCode,
                        'result_desc' => $queryResult['result_desc'] ?? '',
                    ]);
                }
                // If result_code is not '0' but not a failure, keep as pending
                // (might be still processing, timeout waiting for user, etc.)
            }
        }

        $subscription = $payment->subscription;
        if ($subscription) {
            $subscription->refresh();
        }

        return response()->json([
            'status' => $payment->transaction_status,
            'transaction' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_number' => $payment->payment_number,
                'mpesa_receipt' => $payment->mpesa_receipt,
            ],
            'subscription_active' => $subscription && $subscription->status === 'active',
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Check payment status by checkout request ID
     * If payment is still pending, query Daraja API to fetch latest status
     */
    public function checkPaymentStatus(Request $request, TenantContext $tenantContext)
    {
        $checkoutRequestId = $request->input('checkout_request_id');

        if (! $checkoutRequestId) {
            return response()->json([
                'success' => false,
                'error' => 'Checkout request ID is required',
            ], 400);
        }

        $tenant = $tenantContext->getTenant();
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'error' => 'Payment not found',
            ], 404);
        }

        // If payment is still pending, query Daraja API to get latest status
        if ($payment->transaction_status === 'pending') {
            $mpesaService = app(MpesaStkService::class);
            $queryResult = $mpesaService->querySTKPushStatus($checkoutRequestId);

            if ($queryResult['success'] && isset($queryResult['is_paid']) && $queryResult['is_paid']) {
                // Payment was successful - process it as if callback was received
                // This simulates the callback processing
                try {
                    DB::beginTransaction();

                    // Update payment status
                    $payment->update([
                        'transaction_status' => 'completed',
                        'reference' => $queryResult['checkout_request_id'] ?? $checkoutRequestId,
                    ]);

                    // Activate subscription if this is a subscription payment
                    if ($payment->subscription_id) {
                        $subscription = $payment->subscription;
                        $subscription->update([
                            'status' => 'active',
                            'started_at' => now(),
                            'ends_at' => now()->addMonth(),
                            'next_billing_at' => now()->addMonth(),
                        ]);
                    }

                    DB::commit();

                    Log::info('Payment status updated via Daraja query', [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $checkoutRequestId,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to update payment status from query', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($queryResult['success'] && isset($queryResult['result_code']) && $queryResult['result_code'] != '0') {
                // Payment failed or cancelled
                $payment->update([
                    'transaction_status' => 'failed',
                ]);
            }

            // Refresh payment to get latest status
            $payment->refresh();
        }

        $isCompleted = $payment->transaction_status === 'completed';
        $subscription = $payment->subscription;

        // Refresh subscription to get latest status
        if ($subscription) {
            $subscription->refresh();
        }

        return response()->json([
            'success' => true,
            'payment_status' => $payment->transaction_status,
            'is_completed' => $isCompleted,
            'subscription_status' => $subscription ? $subscription->status : null,
            'subscription_active' => $subscription && $subscription->status === 'active',
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Handle upgrade request with payment method selection
     */
    public function upgrade(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;

        // Custom validation based on payment gateway
        $rules = [
            'plan' => 'required|in:starter,business,enterprise',
            'payment_gateway' => 'required|in:mpesa,debit_card,credit_card,paypal',
        ];

        $messages = [];

        // Add conditional validation based on payment gateway
        if ($request->input('payment_gateway') === 'mpesa') {
            $rules['mpesa_phone'] = 'required|string|regex:/^254\d{9}$/';
            $messages['mpesa_phone.required'] = 'Phone number is required for M-Pesa payments.';
            $messages['mpesa_phone.regex'] = 'Phone number must be in format 254712345678.';
        } elseif (in_array($request->input('payment_gateway'), ['debit_card', 'credit_card'])) {
            // Placeholder validation - these methods are not yet implemented
            $rules['card_number'] = 'required|string';
            $rules['cardholder_name'] = 'required|string';
            $rules['expiry_month'] = 'required|string';
            $rules['expiry_year'] = 'required|string';
            $rules['cvv'] = 'required|string';
        } elseif ($request->input('payment_gateway') === 'paypal') {
            // Placeholder validation - PayPal is not yet implemented
            $rules['paypal_email'] = 'required|email';
            $rules['paypal_password'] = 'required|string';
            $messages['paypal_email.required'] = 'PayPal email is required for PayPal payments.';
            $messages['paypal_password.required'] = 'PayPal password is required for PayPal payments.';
        }

        $validated = $request->validate($rules, $messages);

        if (! $subscription) {
            return back()->withErrors(['plan' => 'No active subscription found.']);
        }

        try {
            DB::beginTransaction();

            // Process payment (demo mode in development, real payment in production)
            $isTestMode = PaymentHelper::isTestMode();
            $paymentData = [];
            $paymentResult = null;

            if ($validated['payment_gateway'] === 'mpesa') {
                // Real M-Pesa payment processing
                $paymentData = [
                    'phone_number' => $validated['mpesa_phone'] ?? null,
                ];
            } elseif (in_array($validated['payment_gateway'], ['debit_card', 'credit_card'])) {
                // Placeholder for card payments
                $paymentData = [
                    'card_number' => str_replace(' ', '', $validated['card_number'] ?? ''),
                    'cardholder_name' => $validated['cardholder_name'] ?? null,
                    'expiry_month' => $validated['expiry_month'] ?? null,
                    'expiry_year' => $validated['expiry_year'] ?? null,
                    'cvv' => $validated['cvv'] ?? null,
                ];
            } elseif ($validated['payment_gateway'] === 'paypal') {
                // Placeholder for PayPal payments
                $paymentData = [
                    'email' => $validated['paypal_email'] ?? null,
                    'password' => $validated['paypal_password'] ?? null,
                ];
            }

            // Store payment details in subscription settings for masked display
            $settings = $subscription->settings ?? [];
            $settings = array_merge($settings, $paymentData);

            // Get plan price
            $planPrices = [
                'starter' => 2500,
                'business' => 5000,
                'enterprise' => 0, // Custom pricing
            ];
            $amount = $planPrices[$validated['plan']] ?? 0;

            if ($validated['payment_gateway'] === 'mpesa') {
                // Initiate M-Pesa STK Push
                $mpesaService = app(MpesaStkService::class);

                if (! $mpesaService->isConfigured()) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'M-Pesa is not configured. Please contact support.',
                        ], 400);
                    }

                    return back()->withErrors(['payment_gateway' => 'M-Pesa is not configured. Please contact support.'])->withInput();
                }

                // Get or create M-Pesa account
                $mpesaAccount = Account::where('tenant_id', $tenant->id)
                    ->where('type', 'mpesa')
                    ->first();

                if (! $mpesaAccount) {
                    $cashAccount = ChartOfAccount::where('tenant_id', $tenant->id)
                        ->where('code', '1400')
                        ->first();

                    if ($cashAccount) {
                        $mpesaAccount = Account::create([
                            'tenant_id' => $tenant->id,
                            'name' => 'M-Pesa',
                            'type' => 'mpesa',
                            'chart_of_account_id' => $cashAccount->id,
                            'is_active' => true,
                        ]);
                    }
                }

                // Generate payment number
                $paymentNumber = 'SUB-'.date('Ymd').'-'.str_pad(
                    Payment::where('tenant_id', $tenant->id)->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create payment record (pending)
                $payment = Payment::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $subscription->id,
                    'payment_number' => $paymentNumber,
                    'payment_date' => now()->toDateString(),
                    'account_id' => $mpesaAccount->id ?? null,
                    'amount' => $amount,
                    'payment_method' => 'mpesa',
                    'phone' => $validated['mpesa_phone'],
                    'transaction_status' => 'pending',
                ]);

                // Initiate STK Push
                $stkResult = $mpesaService->initiateSTKPush([
                    'phone_number' => $validated['mpesa_phone'],
                    'amount' => $amount,
                    'account_reference' => 'SUB-'.$subscription->id,
                    'transaction_desc' => 'Subscription payment for '.ucfirst($validated['plan']).' plan',
                ]);

                if (! $stkResult['success']) {
                    DB::rollBack();
                    if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'error' => $stkResult['error'] ?? 'Failed to initiate M-Pesa payment. Please try again.',
                        ], 400);
                    }

                    return back()->withErrors(['payment_gateway' => $stkResult['error'] ?? 'Failed to initiate M-Pesa payment. Please try again.'])->withInput();
                }

                // Update payment with STK push details
                $payment->update([
                    'checkout_request_id' => $stkResult['checkoutRequestID'],
                    'merchant_request_id' => $stkResult['merchantRequestID'],
                ]);

                // Update subscription (pending payment)
                $subscription->update([
                    'plan' => $validated['plan'],
                    'status' => 'pending', // Will be activated on payment confirmation
                    'payment_gateway' => 'mpesa',
                    'settings' => $settings,
                ]);

                DB::commit();

                // Always return JSON for AJAX requests, otherwise redirect
                if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'M-Pesa STK push initiated. Please check your phone ('.$validated['mpesa_phone'].') to complete the payment. Your subscription will be activated once payment is confirmed.',
                        'checkout_request_id' => $stkResult['checkoutRequestID'],
                        'customer_message' => $stkResult['customerMessage'],
                    ], 200);
                }

                return redirect()->route('tenant.billing.page')
                    ->with('info', 'M-Pesa STK push sent to '.$validated['mpesa_phone'].'. Please complete the payment on your phone. Your subscription will be activated once payment is confirmed.');

            } else {
                // Placeholder payment processing for card/PayPal (not yet implemented)
                $paymentResult = [
                    'success' => true,
                    'is_demo' => true,
                    'message' => 'Payment method not yet implemented. This is a placeholder.',
                ];

                // Update subscription settings with transaction info
                if ($paymentResult && isset($paymentResult['transaction_id'])) {
                    $settings['transaction_id'] = $paymentResult['transaction_id'];
                }
                if ($paymentResult && isset($paymentResult['is_demo'])) {
                    $settings['is_demo_payment'] = $paymentResult['is_demo'];
                }

                $subscription->update([
                    'plan' => $validated['plan'],
                    'status' => 'active',
                    'started_at' => now(),
                    'payment_gateway' => $validated['payment_gateway'],
                    'settings' => $settings,
                ]);

                DB::commit();

                return redirect()->route('tenant.users.index')
                    ->with('success', 'Upgrade successful — You can now invite users.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Upgrade failed: '.$e->getMessage(), [
                'exception' => $e,
                'tenant_id' => $tenant->id,
                'request_data' => $request->except(['_token']),
            ]);

            $errorMessage = 'An error occurred while processing your upgrade. Please try again.';
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                ], 500);
            }

            return back()->withErrors(['payment_gateway' => $errorMessage])->withInput();
        }
    }
}
