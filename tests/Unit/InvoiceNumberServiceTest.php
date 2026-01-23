<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberService;
    }

    /**
     * Test that generate() returns expected format INV-{SEQUENCE}
     */
    public function test_generate_returns_expected_format(): void
    {
        $tenant = $this->createTestTenant();

        $invoiceNumber = $this->service->generate($tenant->id);

        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertMatchesRegularExpression('/^INV-\d{3}$/', $invoiceNumber);
    }

    /**
     * Test that sequence increments across multiple generate calls for same tenant
     */
    public function test_sequence_increments_for_same_tenant(): void
    {
        $tenant = $this->createTestTenant();

        $first = $this->service->generate($tenant->id);
        $second = $this->service->generate($tenant->id);
        $third = $this->service->generate($tenant->id);

        // Extract sequence numbers
        preg_match('/INV-(\d{3})$/', $first, $matches1);
        preg_match('/INV-(\d{3})$/', $second, $matches2);
        preg_match('/INV-(\d{3})$/', $third, $matches3);

        $seq1 = (int) $matches1[1];
        $seq2 = (int) $matches2[1];
        $seq3 = (int) $matches3[1];

        $this->assertEquals(1, $seq1);
        $this->assertEquals(2, $seq2);
        $this->assertEquals(3, $seq3);
    }

    /**
     * Test that different tenants have independent sequences
     */
    public function test_different_tenants_have_independent_sequences(): void
    {
        $tenant1 = $this->createTestTenant();
        $tenant2 = $this->createTestTenant();

        $number1 = $this->service->generate($tenant1->id);
        $number2 = $this->service->generate($tenant2->id);

        // Both should start at 001
        preg_match('/INV-(\d{3})$/', $number1, $matches1);
        preg_match('/INV-(\d{3})$/', $number2, $matches2);

        $this->assertEquals('001', $matches1[1]);
        $this->assertEquals('001', $matches2[1]);
    }

    /**
     * Test that generate creates counter if it doesn't exist
     */
    public function test_generate_creates_counter_if_not_exists(): void
    {
        $tenant = $this->createTestTenant();

        $this->assertDatabaseMissing('invoice_counters', [
            'tenant_id' => $tenant->id,
        ]);

        $this->service->generate($tenant->id);

        $this->assertDatabaseHas('invoice_counters', [
            'tenant_id' => $tenant->id,
            'sequence' => 1,
        ]);
    }

    /**
     * Helper method to create a test tenant
     */
    protected function createTestTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Test Tenant '.uniqid(),
            'email' => 'test'.uniqid().'@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
    }
}
