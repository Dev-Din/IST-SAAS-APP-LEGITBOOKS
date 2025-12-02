<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Services\MpesaStkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaBillingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'tenant_hash' => 'test-tenant-hash',
            'status' => 'active',
        ]);

        // Create user
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_owner' => true,
            'is_active' => true,
        ]);

        // Create subscription
        $this->subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan' => 'plan_free',
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Create Chart of Account
        $cashAccount = ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1400',
            'name' => 'Cash',
            'type' => 'asset',
            'is_active' => true,
        ]);

        // Create M-Pesa account
        Account::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'M-Pesa',
            'type' => 'mpesa',
            'chart_of_account_id' => $cashAccount->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function tenant_can_initiate_mpesa_stk_push_for_subscription()
    {
        // Mock M-Pesa API response
        Http::fake([
            'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-access-token',
            ], 200),
            'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'ResponseCode' => '0',
                'CheckoutRequestID' => 'ws_CO_TEST123',
                'MerchantRequestID' => 'MERCHANT_TEST123',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->postJson(route('tenant.billing.mpesa.initiate', ['tenant_hash' => $this->tenant->tenant_hash]), [
                'plan' => 'starter',
                'phone' => '254712345678',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ])
            ->assertJsonStructure([
                'ok',
                'checkoutRequestID',
                'message',
            ]);

        // Assert payment was created
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'checkout_request_id' => 'ws_CO_TEST123',
            'transaction_status' => 'pending',
            'phone' => '254712345678',
            'amount' => 2500.00,
        ]);

        // Assert subscription was updated
        $this->subscription->refresh();
        $this->assertEquals('starter', $this->subscription->plan);
        $this->assertEquals('pending', $this->subscription->status);
    }

    /** @test */
    public function polling_endpoint_returns_payment_status()
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('type', 'mpesa')->first();
        
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_number' => 'PAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'account_id' => $account->id,
            'amount' => 2500.00,
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_TEST123',
            'transaction_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'web')
            ->getJson(route('tenant.billing.mpesa.status', ['tenant_hash' => $this->tenant->tenant_hash, 'checkoutRequestID' => 'ws_CO_TEST123']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'transaction',
                'subscription_active',
            ])
            ->assertJson([
                'status' => 'pending',
            ]);
    }

    /** @test */
    public function callback_activates_subscription_on_successful_payment()
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('type', 'mpesa')->first();
        
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_number' => 'PAY-TEST-002',
            'payment_date' => now()->toDateString(),
            'account_id' => $account->id,
            'amount' => 2500.00,
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_TEST123',
            'transaction_status' => 'pending',
        ]);

        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_TEST123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1.00],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST123456'],
                            ['Name' => 'PhoneNumber', 'Value' => '254712345678'],
                            ['Name' => 'TransactionDate', 'Value' => '20251202230000'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        $response->assertStatus(200)
            ->assertJson([
                'ResultCode' => 0,
                'ResultDesc' => 'Payment processed successfully',
            ]);

        // Assert payment was updated
        $payment->refresh();
        $this->assertEquals('completed', $payment->transaction_status);
        $this->assertEquals('TEST123456', $payment->mpesa_receipt);

        // Assert subscription was activated
        $this->subscription->refresh();
        $this->assertEquals('active', $this->subscription->status);
        $this->assertNotNull($this->subscription->started_at);
    }

    /** @test */
    public function callback_marks_payment_as_failed_on_failure()
    {
        $account = Account::where('tenant_id', $this->tenant->id)->where('type', 'mpesa')->first();
        
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'payment_number' => 'PAY-TEST-003',
            'payment_date' => now()->toDateString(),
            'account_id' => $account->id,
            'amount' => 2500.00,
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_TEST123',
            'transaction_status' => 'pending',
        ]);

        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_TEST123',
                    'ResultCode' => 1032,
                    'ResultDesc' => 'Request cancelled by user',
                ],
            ],
        ];

        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        $response->assertStatus(200);

        // Assert payment was marked as failed
        $payment->refresh();
        $this->assertEquals('failed', $payment->transaction_status);
    }

    /** @test */
    public function phone_number_validation_enforces_format()
    {
        $response = $this->actingAs($this->user, 'web')
            ->postJson(route('tenant.billing.mpesa.initiate', ['tenant_hash' => $this->tenant->tenant_hash]), [
                'plan' => 'starter',
                'phone' => '0712345678', // Invalid format
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }
}

