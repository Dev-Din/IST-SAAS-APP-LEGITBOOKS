<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Services\TenantContext;
use App\Helpers\PaymentHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (!$subscription) {
            return back()->withErrors(['plan' => 'No active subscription found.']);
        }

        $subscription->update([
            'plan' => $validated['plan'],
        ]);

        return back()->with('success', 'Subscription plan updated successfully.');
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
     * Handle upgrade request with payment method selection
     */
    public function upgrade(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->getTenant();
        $subscription = $tenant->subscription;

        $validated = $request->validate([
            'plan' => 'required|in:starter,business,enterprise',
            'payment_gateway' => 'required|in:mpesa,debit_card,credit_card,paypal',
            // M-Pesa fields
            'mpesa_phone' => 'required_if:payment_gateway,mpesa|nullable|string',
            'mpesa_name' => 'required_if:payment_gateway,mpesa|nullable|string',
            // Card fields
            'card_number' => 'required_if:payment_gateway,debit_card,credit_card|nullable|string',
            'cardholder_name' => 'required_if:payment_gateway,debit_card,credit_card|nullable|string',
            'expiry_month' => 'required_if:payment_gateway,debit_card,credit_card|nullable|string',
            'expiry_year' => 'required_if:payment_gateway,debit_card,credit_card|nullable|string',
            'cvv' => 'required_if:payment_gateway,debit_card,credit_card|nullable|string',
            // PayPal fields
            'paypal_email' => 'required_if:payment_gateway,paypal|nullable|email',
            'paypal_password' => 'required_if:payment_gateway,paypal|nullable|string',
        ]);

        if (!$subscription) {
            return back()->withErrors(['plan' => 'No active subscription found.']);
        }

        try {
            DB::beginTransaction();

            // Process payment (demo mode in development, real payment in production)
            $isTestMode = PaymentHelper::isTestMode();
            $paymentData = [];
            $paymentResult = null;

            if ($validated['payment_gateway'] === 'mpesa') {
                $paymentData = [
                    'phone_number' => $validated['mpesa_phone'] ?? null,
                    'name' => $validated['mpesa_name'] ?? null,
                ];
            } elseif (in_array($validated['payment_gateway'], ['debit_card', 'credit_card'])) {
                $paymentData = [
                    'card_number' => str_replace(' ', '', $validated['card_number'] ?? ''),
                    'cardholder_name' => $validated['cardholder_name'] ?? null,
                    'expiry_month' => $validated['expiry_month'] ?? null,
                    'expiry_year' => $validated['expiry_year'] ?? null,
                    'cvv' => $validated['cvv'] ?? null,
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

            // Update subscription
            $settings = $subscription->settings ?? [];
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

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Upgrade failed: ' . $e->getMessage(), [
                'exception' => $e,
                'tenant_id' => $tenant->id,
            ]);

            return back()->withErrors(['plan' => 'An error occurred while processing your upgrade. Please try again.']);
        }
    }
}
