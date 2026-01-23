<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MpesaStkService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected MpesaStkService $mpesaService
    ) {}

    /**
     * Initiate M-Pesa payment for plan purchase
     * POST /app/checkout/{plan}/pay-mpesa
     */
    public function payWithMpesa(Request $request, string $plan)
    {
        $tenant = $this->tenantContext->getTenant();

        // Validate plan
        $validPlans = ['starter', 'business', 'enterprise'];
        if (! in_array($plan, $validPlans)) {
            return back()->withErrors(['plan' => 'Invalid plan selected.']);
        }

        // Get plan pricing
        $planPrices = [
            'starter' => 2500,
            'business' => 5000,
            'enterprise' => 0, // Custom pricing
        ];

        $amount = $planPrices[$plan];
        if ($amount <= 0 && $plan === 'enterprise') {
            return back()->withErrors(['plan' => 'Enterprise plan requires custom pricing. Please contact support.']);
        }

        // Validate phone number
        $request->validate([
            'phone_number' => 'required|string|regex:/^(\+?254|0)[0-9]{9}$/',
        ]);

        $phoneNumber = $this->formatPhoneNumber($request->phone_number);

        try {
            DB::beginTransaction();

            // Get or create subscription
            $subscription = $tenant->subscription;
            if (! $subscription) {
                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan' => $plan,
                    'status' => 'pending',
                    'payment_gateway' => 'mpesa',
                ]);
            } else {
                $subscription->update([
                    'plan' => $plan,
                    'status' => 'pending',
                    'payment_gateway' => 'mpesa',
                ]);
            }

            // Generate secure client token (UUID)
            $clientToken = (string) Str::uuid();

            // Create payment record
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => 'KES',
                'payment_method' => 'mpesa',
                'phone' => $phoneNumber,
                'transaction_status' => 'pending',
                'client_token' => $clientToken,
                'payment_date' => now()->toDateString(),
            ]);

            // Generate merchant reference
            $merchantReference = 'SUB-'.$subscription->id.'-'.time();

            // Initiate STK Push
            $stkResult = $this->mpesaService->initiateSTKPush([
                'phone_number' => $phoneNumber,
                'amount' => $amount,
                'account_reference' => $merchantReference,
                'transaction_desc' => 'Subscription payment for '.ucfirst($plan).' plan',
            ]);

            if (! $stkResult['success']) {
                DB::rollBack();

                return back()->withErrors(['payment' => $stkResult['error'] ?? 'Failed to initiate M-Pesa payment.']);
            }

            // Update payment with STK push details
            $payment->update([
                'checkout_request_id' => $stkResult['checkoutRequestID'],
                'merchant_request_id' => $stkResult['merchantRequestID'],
                'reference' => $merchantReference,
            ]);

            DB::commit();

            // Return waiting page with client token
            return view('tenant.checkout.waiting', [
                'plan' => $plan,
                'client_token' => $clientToken,
                'amount' => $amount,
                'phone' => $phoneNumber,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('M-Pesa checkout error', [
                'plan' => $plan,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['payment' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Poll endpoint for payment status
     * GET /app/checkout/{plan}/mpesa-status/{token}
     */
    public function mpesaStatus(string $plan, string $token)
    {
        $tenant = $this->tenantContext->getTenant();

        // Find payment by client token
        $payment = Payment::where('client_token', $token)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $payment) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Payment not found',
            ], 404);
        }

        // Return status based on transaction_status
        $status = match ($payment->transaction_status) {
            'completed' => 'success',
            'failed', 'cancelled' => 'failed',
            default => 'pending',
        };

        // Also check if subscription is active (for subscription payments)
        if ($status === 'success' && $payment->subscription_id) {
            $subscription = $payment->subscription;
            if ($subscription && $subscription->status !== 'active') {
                // Payment completed but subscription not yet activated - still pending
                $status = 'pending';
            }
        }

        $response = [
            'status' => $status,
        ];

        if ($status === 'success') {
            $response['redirect'] = route('tenant.dashboard').'?payment=success';
            $response['message'] = 'Payment received — redirecting...';
        } elseif ($status === 'failed') {
            $response['error'] = 'Payment failed. Please try again.';
        }

        return response()->json($response);
    }

    /**
     * Format phone number to 254XXXXXXXXX format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // If starts with 0, replace with 254
        if (substr($phone, 0, 1) === '0') {
            $phone = '254'.substr($phone, 1);
        }

        // If doesn't start with 254, add it
        if (substr($phone, 0, 3) !== '254') {
            $phone = '254'.$phone;
        }

        return $phone;
    }
}
