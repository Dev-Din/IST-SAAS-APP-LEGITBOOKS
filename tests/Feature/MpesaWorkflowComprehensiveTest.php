<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ChartOfAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MpesaStkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MpesaWorkflowComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Contact $contact;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant using factory
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'status' => 'active',
        ]);

        // Create Chart of Accounts
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1400',
            'name' => 'Cash',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);

        // Create user
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create contact
        $this->contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '254712345678',
            'type' => 'customer',
        ]);

        // Create invoice
        $this->invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'contact_id' => $this->contact->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'payment_status' => 'pending',
            'subtotal' => 1000,
            'tax' => 160,
            'total' => 1160,
            'payment_token' => \Illuminate\Support\Str::random(32),
        ]);

        InvoiceLineItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);
    }

    /** @test */
    public function test_complete_mpesa_workflow_with_callback()
    {

        // Mock M-Pesa STK Push API
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599',
            ], 200),
            '*/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => 'test-merchant-123',
                'CheckoutRequestID' => 'test-checkout-456',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        // Step 1: Initiate STK Push
        $response = $this->postJson("/pay/{$this->invoice->id}/{$this->invoice->payment_token}/mpesa", [
            'phone_number' => '254712345678',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $checkoutRequestId = $response->json('checkoutRequestID');
        $this->assertNotNull($checkoutRequestId);

        // Verify payment record created
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->transaction_status);
        $this->assertNull($payment->raw_callback);
        $this->assertNull($payment->mpesa_receipt);

        // Step 2: Simulate M-Pesa callback
        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'test-merchant-123',
                    'CheckoutRequestID' => $checkoutRequestId,
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1160],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TEST123456'],
                            ['Name' => 'TransactionDate', 'Value' => 20260207120000],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ];

        $callbackResponse = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        $callbackResponse->assertStatus(200);

        // Step 3: Verify payment updated
        $payment->refresh();

        $this->assertEquals('completed', $payment->transaction_status);
        $this->assertEquals('TEST123456', $payment->mpesa_receipt);
        $this->assertNotNull($payment->raw_callback);
        $this->assertIsArray($payment->raw_callback);

        // Step 4: Verify invoice paid
        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals('paid', $this->invoice->payment_status);

        // Step 5: Verify payment allocation created
        $allocation = $this->invoice->paymentAllocations()->first();
        $this->assertNotNull($allocation);
        $this->assertEquals($payment->id, $allocation->payment_id);
        $this->assertEquals(1160, $allocation->amount);

        // Step 6: Verify journal entry created
        $journalEntry = $payment->journalEntry;
        $this->assertNotNull($journalEntry);
        $this->assertTrue($journalEntry->isBalanced());
    }

    /** @test */
    public function test_mpesa_workflow_with_sync_path_no_callback()
    {

        // Mock M-Pesa APIs
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599',
            ], 200),
            '*/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => 'test-merchant-789',
                'CheckoutRequestID' => 'test-checkout-999',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
            '*/mpesa/stkpushquery/v1/query' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'The service request has been accepted successfully',
                'MerchantRequestID' => 'test-merchant-789',
                'CheckoutRequestID' => 'test-checkout-999',
                'ResultCode' => '0',
                'ResultDesc' => 'The service request is processed successfully.',
            ], 200),
        ]);

        // Step 1: Initiate STK Push
        $response = $this->postJson("/pay/{$this->invoice->id}/{$this->invoice->payment_token}/mpesa", [
            'phone_number' => '254712345678',
        ]);

        $response->assertStatus(200);
        $checkoutRequestId = $response->json('checkoutRequestID');

        // Verify payment record created
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->transaction_status);

        // Step 2: Simulate success page load (triggers sync)
        // Note: Callback never arrives, so sync path should complete the payment
        $successResponse = $this->get("/pay/{$this->invoice->id}/{$this->invoice->payment_token}/success?checkout_request_id={$checkoutRequestId}");

        // Should redirect to receipt since sync completed the payment
        $successResponse->assertStatus(302);
        $successResponse->assertRedirect("/pay/{$this->invoice->id}/{$this->invoice->payment_token}/receipt");

        // Step 3: Verify payment completed via sync
        $payment->refresh();

        $this->assertEquals('completed', $payment->transaction_status);
        $this->assertNotNull($payment->raw_callback);
        $this->assertIsArray($payment->raw_callback);
        $this->assertEquals('daraja_stk_query', $payment->raw_callback['_source']);
        $this->assertArrayHasKey('_queried_at', $payment->raw_callback);
        // Receipt will be null when completed via sync (query API doesn't return receipt)
        $this->assertNull($payment->mpesa_receipt);

        // Step 4: Verify invoice paid
        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals('paid', $this->invoice->payment_status);

        // Step 5: Verify payment allocated
        $allocation = $this->invoice->paymentAllocations()->first();
        $this->assertNotNull($allocation);
        $this->assertEquals($payment->id, $allocation->payment_id);
    }

    /** @test */
    public function test_callback_with_lowercase_body_key()
    {

        // Create payment manually
        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'PAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'amount' => 1160,
            'payment_method' => 'mpesa',
            'phone' => '254712345678',
            'transaction_status' => 'pending',
            'checkout_request_id' => 'test-lowercase-checkout',
            'merchant_request_id' => 'test-lowercase-merchant',
        ]);

        // Test callback with lowercase 'body' key (defensive parsing)
        $callbackPayload = [
            'body' => [ // lowercase instead of 'Body'
                'stkCallback' => [
                    'MerchantRequestID' => 'test-lowercase-merchant',
                    'CheckoutRequestID' => 'test-lowercase-checkout',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1160],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'LOWER123'],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/payments/mpesa/callback', $callbackPayload);

        $response->assertStatus(200);

        $payment->refresh();
        $this->assertEquals('completed', $payment->transaction_status);
        $this->assertEquals('LOWER123', $payment->mpesa_receipt);
    }

    /** @test */
    public function test_callback_test_endpoint()
    {
        $response = $this->postJson('/api/payments/mpesa/callback-test', [
            'test' => 'data',
            'value' => 123,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertArrayHasKey('received_at', $response->json());
    }
}
