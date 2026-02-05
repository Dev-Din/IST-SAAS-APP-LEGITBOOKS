<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentStatusEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);

        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'type' => 'customer',
        ]);

        $token = '15F8u3ZqGkNkdOHXocHldxWJvLbCgwBbICnzwIis9rUToN9DgTLIQyOxTTcYaUbI';

        $this->invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-2025-0001',
            'contact_id' => $contact->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'sent',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'total' => 1000.00,
            'payment_token' => $token,
            'payment_status' => 'pending',
        ]);
    }

    public function test_status_endpoint_returns_400_when_checkout_request_id_missing(): void
    {
        $url = "/pay/{$this->invoice->id}/{$this->invoice->payment_token}/status";

        $response = $this->getJson($url);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'error' => 'Checkout request ID is required',
            ]);
    }

    public function test_status_endpoint_returns_404_when_token_invalid(): void
    {
        $url = "/pay/{$this->invoice->id}/invalid-token/status?checkout_request_id=ws_CO_123";

        $response = $this->getJson($url);

        $response->assertStatus(404);
    }

    public function test_status_endpoint_returns_404_when_payment_not_found_for_checkout_request_id(): void
    {
        $url = "/pay/{$this->invoice->id}/{$this->invoice->payment_token}/status?checkout_request_id=ws_CO_nonexistent";

        $response = $this->getJson($url);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'error' => 'Payment not found',
            ]);
    }

    public function test_status_endpoint_returns_success_when_payment_completed_and_invoice_paid(): void
    {
        $checkoutRequestId = 'ws_CO_' . uniqid();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'PAY-' . date('Ymd') . '-' . $this->tenant->id . '-0001',
            'payment_date' => now(),
            'amount' => 1000.00,
            'payment_method' => 'mpesa',
            'transaction_status' => 'pending',
            'checkout_request_id' => $checkoutRequestId,
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 1000.00,
        ]);

        $payment->update(['transaction_status' => 'completed']);
        $this->invoice->update(['status' => 'paid', 'payment_status' => 'paid']);

        $url = "/pay/{$this->invoice->id}/{$this->invoice->payment_token}/status?checkout_request_id="
            . urlencode($checkoutRequestId);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'invoice_paid' => true,
                'outstanding' => 0,
            ])
            ->assertJsonStructure([
                'status',
                'payment_status',
                'invoice_paid',
                'outstanding',
            ]);
    }

    public function test_status_endpoint_returns_pending_when_payment_still_pending(): void
    {
        $this->mock(\App\Services\MpesaStkService::class, function ($mock) {
            $mock->shouldReceive('querySTKPushStatus')
                ->andReturn([
                    'success' => true,
                    'is_paid' => false,
                    'result_code' => '4999',
                ]);
        });

        $checkoutRequestId = 'ws_CO_' . uniqid();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'PAY-' . date('Ymd') . '-' . $this->tenant->id . '-0002',
            'payment_date' => now(),
            'amount' => 1000.00,
            'payment_method' => 'mpesa',
            'transaction_status' => 'pending',
            'checkout_request_id' => $checkoutRequestId,
        ]);

        $url = "/pay/{$this->invoice->id}/{$this->invoice->payment_token}/status?checkout_request_id="
            . urlencode($checkoutRequestId);

        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'pending',
                'invoice_paid' => false,
                'outstanding' => 1000.0,
            ]);
    }
}
