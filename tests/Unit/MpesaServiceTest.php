<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\Tenant;
use App\Services\MpesaService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MpesaService $service;

    protected Tenant $tenant;

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
        // COA 1400 (Cash) required for M-Pesa account creation in processCallback
        ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1400',
            'name' => 'Cash',
            'type' => 'asset',
            'category' => 'current_asset',
            'is_active' => true,
        ]);
        $tenantContext = app(TenantContext::class);
        $tenantContext->setTenant($this->tenant);
        $this->service = app(MpesaService::class);
    }

    public function test_parse_callback_returns_valid_for_stk_success(): void
    {
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_123',
                    'MerchantRequestID' => 'merchant_123',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 100.00],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'RFT123'],
                            ['Name' => 'PhoneNumber', 'Value' => '254712345678'],
                        ],
                    ],
                ],
            ],
        ];

        $parsed = $this->service->parseCallback($payload);

        $this->assertTrue($parsed['valid']);
        $this->assertTrue($parsed['success']);
        $this->assertEquals('ws_CO_123', $parsed['checkout_request_id']);
        $this->assertEquals('RFT123', $parsed['mpesa_receipt']);
        $this->assertEquals(100.00, $parsed['amount']);
        $this->assertEquals('254712345678', $parsed['phone']);
    }

    public function test_parse_callback_returns_invalid_when_stk_callback_missing(): void
    {
        $parsed = $this->service->parseCallback([]);

        $this->assertFalse($parsed['valid']);
        $this->assertArrayHasKey('error', $parsed);
    }

    public function test_simulate_payment_creates_payment(): void
    {
        $result = $this->service->simulatePayment('254712345678', 500.00);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['payment']);
        $this->assertEquals(500.00, $result['payment']->amount);
        $this->assertEquals('mpesa', $result['payment']->payment_method);
    }
}
