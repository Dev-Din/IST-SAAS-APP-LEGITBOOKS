<?php

namespace Tests\Feature\Integration;

use App\Models\InvoiceCounter;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantCreationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_tenant_creation_and_provisioning(): void
    {
        // Create tenant
        $tenant = Tenant::create([
            'name' => 'New Company',
            'email' => 'company@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);

        // Provision tenant
        $provisioningService = app(TenantProvisioningService::class);
        $provisioningService->provision($tenant, [
            'create_admin' => true,
            'admin_email' => 'admin@company.com',
            'admin_password' => 'password123',
            'seed_demo_data' => false,
        ]);

        // Verify invoice counter created
        $counter = InvoiceCounter::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($counter);
        $this->assertEquals(now()->year, $counter->year);
        $this->assertEquals(0, $counter->sequence);

        // Verify admin user created
        $admin = User::where('tenant_id', $tenant->id)
            ->where('email', 'admin@company.com')
            ->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('password123', $admin->password));
    }

    public function test_tenant_registration_creates_owner_user(): void
    {
        $tenant = Tenant::create([
            'name' => 'New Company',
            'email' => 'owner@company.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => 'owner@company.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => true,
            'permissions' => [],
        ]);

        $this->assertTrue($user->is_owner);
        $this->assertTrue($user->hasPermission('any_permission')); // Owner has all permissions
    }
}
