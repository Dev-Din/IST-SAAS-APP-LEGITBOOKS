<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use App\Services\TenantProvisioningService;
use App\Helpers\PaymentHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TenantRegistrationController extends Controller
{
    public function showRegistrationForm()
    {
        return view('tenant.auth.register');
    }

    public function register(Request $request, TenantProvisioningService $provisioningService)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_country_code' => 'nullable|string|size:2|in:KE,TZ,UG|required_with:phone_number',
            'phone_number' => 'nullable|string|max:20|required_with:phone_country_code',
            'accept_terms' => 'required|accepted',
        ]);

        // Check if tenant email already exists
        $tenantEmailExists = Tenant::where('email', $validated['email'])->exists();
        if ($tenantEmailExists) {
            return back()->withErrors([
                'email' => 'This email is already registered. Please sign in instead.',
            ])->withInput();
        }

        try {
            DB::beginTransaction();

            // Create tenant
            $tenant = Tenant::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'tenant_hash' => Tenant::generateTenantHash(),
                'status' => 'active',
                'settings' => [
                    'branding_override' => null,
                ],
            ]);

            // Provision tenant (COA, invoice counter, etc.)
            $provisioningService->provision($tenant, [
                'create_admin' => false, // We'll create the user manually
                'seed_demo_data' => false, // Don't seed demo data for new signups
            ]);

            // Create user account
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'phone_country_code' => $validated['phone_country_code'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
            ]);

            DB::commit();

            // Store tenant and user in session for billing step
            $request->session()->put('registration.tenant_id', $tenant->id);
            $request->session()->put('registration.user_id', $user->id);
            $request->session()->put('registration.password', $validated['password']);

            // Redirect to billing step
            return redirect()->route('tenant.auth.billing');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the actual error for debugging
            \Log::error('Tenant registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'input' => $request->except('password', 'password_confirmation'),
            ]);
            
            // Return more specific error message
            $errorMessage = 'An error occurred during registration. Please try again.';
            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            return back()->withErrors([
                'email' => $errorMessage,
            ])->withInput();
        }
    }

    public function showBillingForm(Request $request)
    {
        // Check if registration data exists in session
        if (!$request->session()->has('registration.tenant_id')) {
            return redirect()->route('tenant.auth.register')
                ->with('error', 'Please complete account registration first.');
        }

        $plans = [
            'free' => [
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

        $isTestMode = PaymentHelper::isTestMode();
        $demoPaymentDetails = [];
        
        if ($isTestMode) {
            foreach (['mpesa', 'debit_card', 'credit_card', 'paypal'] as $gateway) {
                $demoPaymentDetails[$gateway] = PaymentHelper::getDemoPaymentDetails($gateway);
            }
        }

        return view('tenant.auth.billing', compact('plans', 'isTestMode', 'demoPaymentDetails'));
    }

    public function processBilling(Request $request)
    {
        // Check if registration data exists in session
        if (!$request->session()->has('registration.tenant_id')) {
            return redirect()->route('tenant.auth.register')
                ->with('error', 'Please complete account registration first.');
        }

        $validated = $request->validate([
            'plan' => 'required|in:free,starter,business,enterprise',
            'payment_gateway' => 'required|in:mpesa,debit_card,credit_card,paypal',
            // Payment details (optional, for demo/testing)
            'mpesa_phone' => 'nullable|string',
            'mpesa_name' => 'nullable|string',
            'debit_card_number' => 'nullable|string',
            'debit_cardholder_name' => 'nullable|string',
            'debit_expiry_month' => 'nullable|string',
            'debit_expiry_year' => 'nullable|string',
            'debit_cvv' => 'nullable|string',
            'credit_card_number' => 'nullable|string',
            'credit_cardholder_name' => 'nullable|string',
            'credit_expiry_month' => 'nullable|string',
            'credit_expiry_year' => 'nullable|string',
            'credit_cvv' => 'nullable|string',
            'paypal_email' => 'nullable|email',
            'paypal_password' => 'nullable|string',
        ]);

        try {
            $tenantId = $request->session()->get('registration.tenant_id');
            $userId = $request->session()->get('registration.user_id');
            $password = $request->session()->get('registration.password');

            $tenant = Tenant::findOrFail($tenantId);
            $user = User::findOrFail($userId);

            // Calculate trial end date (14 days from now)
            $trialEndsAt = now()->addDays(14);

            // Process payment (demo mode in development)
            $isTestMode = PaymentHelper::isTestMode();
            $paymentData = [];
            if ($validated['payment_gateway'] === 'mpesa') {
                $paymentData = [
                    'phone_number' => $validated['mpesa_phone'] ?? null,
                    'name' => $validated['mpesa_name'] ?? null,
                ];
            } elseif ($validated['payment_gateway'] === 'debit_card') {
                $paymentData = [
                    'card_number' => $validated['debit_card_number'] ?? null,
                    'cardholder_name' => $validated['debit_cardholder_name'] ?? null,
                    'expiry_month' => $validated['debit_expiry_month'] ?? null,
                    'expiry_year' => $validated['debit_expiry_year'] ?? null,
                    'cvv' => $validated['debit_cvv'] ?? null,
                ];
            } elseif ($validated['payment_gateway'] === 'credit_card') {
                $paymentData = [
                    'card_number' => $validated['credit_card_number'] ?? null,
                    'cardholder_name' => $validated['credit_cardholder_name'] ?? null,
                    'expiry_month' => $validated['credit_expiry_month'] ?? null,
                    'expiry_year' => $validated['credit_expiry_year'] ?? null,
                    'cvv' => $validated['credit_cvv'] ?? null,
                ];
            } elseif ($validated['payment_gateway'] === 'paypal') {
                $paymentData = [
                    'email' => $validated['paypal_email'] ?? null,
                    'password' => $validated['paypal_password'] ?? null,
                ];
            }

            if ($isTestMode) {
                // Use demo payment processing
                $paymentResult = PaymentHelper::processDemoPayment(
                    $validated['payment_gateway'],
                    $paymentData
                );
            } else {
                // In production, integrate with actual payment gateways here
                // For now, we'll treat it as successful
                $paymentResult = ['success' => true, 'is_demo' => false];
            }

            // Create subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan' => $validated['plan'],
                'payment_gateway' => $validated['payment_gateway'],
                'trial_ends_at' => $trialEndsAt,
                'status' => 'trial',
                'vat_applied' => false,
            ]);

            // Store payment transaction info in subscription settings
            $settings = [];
            if ($paymentResult && isset($paymentResult['transaction_id'])) {
                $settings['transaction_id'] = $paymentResult['transaction_id'];
            }
            if ($paymentResult && isset($paymentResult['is_demo'])) {
                $settings['is_demo_payment'] = $paymentResult['is_demo'];
            }
            if (!empty($settings)) {
                $subscription->update(['settings' => $settings]);
            }

            // Clear registration session data
            $request->session()->forget('registration');

            // Auto-login the user
            Auth::login($user);

            // Store tenant in session
            $request->session()->put('tenant_id', $tenant->id);

            $successMessage = 'Welcome to LegitBooks! Your account has been created successfully. You are on a 14-day free trial.';
            if ($isTestMode && $paymentResult && ($paymentResult['is_demo'] ?? false)) {
                $successMessage .= ' (Demo payment processed - Development Mode)';
            }

            return redirect()->route('tenant.dashboard')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            \Log::error('Billing processing failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'plan' => 'An error occurred while processing your subscription. Please try again.',
            ])->withInput();
        }
    }
}

