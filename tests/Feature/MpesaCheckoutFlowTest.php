<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock M-Pesa service to avoid actual API calls in tests
    }

    /**
     * Test STK push initiation for plan purchase
     */
    public function test_pay_with_mpesa_initiates_stk_push(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        $response = $this->post(route('tenant.checkout.pay-mpesa', ['plan' => 'starter']), [
            'phone_number' => '254712345678',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('tenant.checkout.waiting');
        $response->assertViewHas(['plan', 'client_token', 'amount', 'phone']);

        // Verify payment was created
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $tenant->id,
            'payment_method' => 'mpesa',
            'transaction_status' => 'pending',
        ]);
    }

    /**
     * Test payment status polling endpoint
     */
    public function test_mpesa_status_polling_returns_pending(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'client_token' => 'test-token-123',
            'transaction_status' => 'pending',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('tenant.checkout.mpesa-status', [
            'plan' => 'starter',
            'token' => 'test-token-123',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'pending',
        ]);
    }

    /**
     * Test payment status polling returns success after callback
     */
    public function test_mpesa_status_polling_returns_success(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'client_token' => 'test-token-456',
            'transaction_status' => 'completed',
            'mpesa_receipt' => 'TEST123456',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('tenant.checkout.mpesa-status', [
            'plan' => 'starter',
            'token' => 'test-token-456',
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        $response->assertJsonStructure([
            'status',
            'redirect',
            'message',
        ]);
    }

    /**
     * Test M-Pesa callback processing
     */
    public function test_mpesa_callback_processes_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan' => 'starter',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'checkout_request_id' => 'test-checkout-request-123',
            'transaction_status' => 'pending',
            'amount' => 2500,
        ]);

        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'test-checkout-request-123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1.00], // Dev amount
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST123456'],
                            ['Name' => 'PhoneNumber', 'Value' => '254712345678'],
                            ['Name' => 'TransactionDate', 'Value' => '20251127120000'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('api.mpesa.callback'), $callbackPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'ResultCode' => 0,
        ]);

        // Verify payment was updated
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_status' => 'completed',
            'mpesa_receipt' => 'TEST123456',
        ]);

        // Verify subscription was activated
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test callback idempotency (already processed)
     */
    public function test_mpesa_callback_idempotency(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id]);

        $payment = Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'checkout_request_id' => 'test-checkout-request-789',
            'transaction_status' => 'completed', // Already processed
        ]);

        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'test-checkout-request-789',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                ],
            ],
        ];

        $response = $this->postJson(route('api.mpesa.callback'), $callbackPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Callback already processed',
        ]);
    }
}
