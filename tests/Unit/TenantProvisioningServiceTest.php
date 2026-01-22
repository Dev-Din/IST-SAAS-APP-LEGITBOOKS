<?php

namespace Tests\Unit;

use App\Models\InvoiceCounter;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantProvisioningService();
    }

    public function test_provision_creates_invoice_counter(): void
    {
        $tenant = $this->createTestTenant();
        
        $this->service->provision($tenant, []);
        
        $counter = InvoiceCounter::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($counter);
        $this->assertEquals(now()->year, $counter->year);
        $this->assertEquals(0, $counter->sequence);
    }

    public function test_provision_creates_admin_user_when_requested(): void
    {
        $tenant = $this->createTestTenant();
        
        $this->service->provision($tenant, [
            'create_admin' => true,
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password123',
        ]);
        
        $user = \App\Models\User::where('tenant_id', $tenant->id)
            ->where('email', 'admin@test.com')
            ->first();
        
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
    }

    public function test_provision_seeds_demo_data_when_requested(): void
    {
        $tenant = $this->createTestTenant();
        
        // Create a sales account first (required for demo product)
        \App\Models\ChartOfAccount::create([
            'tenant_id' => $tenant->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'category' => 'revenue',
            'is_active' => true,
        ]);
        
        $this->service->provision($tenant, [
            'seed_demo_data' => true,
        ]);
        
        $contact = \App\Models\Contact::where('tenant_id', $tenant->id)
            ->where('email', 'demo@example.com')
            ->first();
        
        $this->assertNotNull($contact);
        
        $product = \App\Models\Product::where('tenant_id', $tenant->id)
            ->where('sku', 'DEMO-001')
            ->first();
        
        $this->assertNotNull($product);
    }

    public function test_provision_does_not_create_admin_when_not_requested(): void
    {
        $tenant = $this->createTestTenant();
        
        $this->service->provision($tenant, [
            'create_admin' => false,
        ]);
        
        $userCount = \App\Models\User::where('tenant_id', $tenant->id)->count();
        $this->assertEquals(0, $userCount);
    }

    protected function createTestTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
    }
}
