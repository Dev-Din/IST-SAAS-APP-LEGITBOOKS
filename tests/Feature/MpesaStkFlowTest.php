<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaStkFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'hash' => 'test-tenant-hash',
        ]);

        // Create user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
        ]);

        // Set tenant context
        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->setTenant($this->tenant);
    }

    /**
     * Test STK initiation creates pending Payment
     */
    public function test_stk_initiation_creates_pending_payment(): void
    {
        // Mock M-Pesa STK service to return success
        $this->mock(\App\Services\MpesaStkService::class, function ($mock) {
            $mock->shouldReceive('initiateSTKPush')
                ->once()
                ->andReturn([
                    'success' => true,
                    'checkoutRequestID' => 'ws_CO_1234567890',
                    'merchantRequestID' => '12345-67890-1',
                    'customerMessage' => 'Success. Request accepted for processing',
                    'response_code' => '0',
                ]);
        });

        // Act as user
        $this->actingAs($this->user, 'web');

        // Create subscription
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'starter',
            'status' => 'pending',
            'payment_gateway' => 'mpesa',
        ]);

        // Make request to initiate STK push
        $response = $this->post(route('tenant.checkout.pay-mpesa', ['plan' => 'starter']), [
            'phone_number' => '254712345678',
        ]);

        // Assert payment was created
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'transaction_status' => 'pending',
            'phone' => '254712345678',
            'amount' => 2500.00,
            'currency' => 'KES',
        ]);

        $payment = Payment::where('tenant_id', $this->tenant->id)
            ->where('subscription_id', $subscription->id)
            ->first();

        $this->assertNotNull($payment);
        $this->assertNotNull($payment->client_token);
        $this->assertEquals('ws_CO_1234567890', $payment->checkout_request_id);
        $this->assertEquals('12345-67890-1', $payment->merchant_request_id);
    }

    /**
     * Test simulated callback updates Payment to success
     */
    public function test_simulated_callback_updates_payment_to_success(): void
    {
        // Create subscription
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'starter',
            'status' => 'pending',
            'payment_gateway' => 'mpesa',
        ]);

        // Create pending payment
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'amount' => 2500.00,
            'currency' => 'KES',
            'payment_method' => 'mpesa',
            'phone' => '254712345678',
            'transaction_status' => 'pending',
            'checkout_request_id' => 'ws_CO_1234567890',
            'merchant_request_id' => '12345-67890-1',
            'payment_date' => now()->toDateString(),
        ]);

        // Simulate M-Pesa callback payload
        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_1234567890',
                    'MerchantRequestID' => '12345-67890-1',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 1.00, // Test amount in dev
                            ],
                            [
                                'Name' => 'MpesaReceiptNumber',
                                'Value' => 'RFT1234567890',
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' => '254712345678',
                            ],
                            [
                                'Name' => 'TransactionDate',
                                'Value' => '20231128123456',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Make callback request
        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        // Assert response
        $response->assertStatus(200);
        $response->assertJson([
            'ResultCode' => 0,
            'ResultDesc' => 'Payment processed successfully',
        ]);

        // Assert payment was updated
        $payment->refresh();
        $this->assertEquals('completed', $payment->transaction_status);
        $this->assertEquals('RFT1234567890', $payment->mpesa_receipt);
        $this->assertNotNull($payment->raw_callback);

        // Assert subscription was activated
        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->started_at);
        $this->assertNotNull($subscription->ends_at);
    }

    /**
     * Test callback idempotency (already processed)
     */
    public function test_callback_idempotency(): void
    {
        // Create subscription
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'starter',
            'status' => 'active',
            'payment_gateway' => 'mpesa',
        ]);

        // Create completed payment
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'amount' => 2500.00,
            'currency' => 'KES',
            'payment_method' => 'mpesa',
            'phone' => '254712345678',
            'transaction_status' => 'completed',
            'checkout_request_id' => 'ws_CO_1234567890',
            'merchant_request_id' => '12345-67890-1',
            'mpesa_receipt' => 'RFT1234567890',
            'payment_date' => now()->toDateString(),
        ]);

        // Simulate duplicate callback
        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_1234567890',
                    'MerchantRequestID' => '12345-67890-1',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                ],
            ],
        ];

        // Make callback request
        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        // Should return 200 with "already processed" message
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Callback already processed',
        ]);

        // Payment should remain unchanged
        $payment->refresh();
        $this->assertEquals('completed', $payment->transaction_status);
    }

    /**
     * Test callback fallback search by phone+amount
     */
    public function test_callback_fallback_search_by_phone_and_amount(): void
    {
        // Create subscription
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'starter',
            'status' => 'pending',
            'payment_gateway' => 'mpesa',
        ]);

        // Create pending payment (without checkout_request_id for fallback test)
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'amount' => 2500.00,
            'currency' => 'KES',
            'payment_method' => 'mpesa',
            'phone' => '254712345678',
            'transaction_status' => 'pending',
            'payment_date' => now()->toDateString(),
        ]);

        // Simulate callback with different checkout_request_id (to trigger fallback)
        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_DIFFERENT',
                    'MerchantRequestID' => 'DIFFERENT',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            [
                                'Name' => 'Amount',
                                'Value' => 1.00,
                            ],
                            [
                                'Name' => 'MpesaReceiptNumber',
                                'Value' => 'RFT1234567890',
                            ],
                            [
                                'Name' => 'PhoneNumber',
                                'Value' => '254712345678',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Make callback request
        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        // Should find payment by phone+amount fallback
        $response->assertStatus(200);

        // Payment should be updated
        $payment->refresh();
        $this->assertEquals('completed', $payment->transaction_status);
    }
}
