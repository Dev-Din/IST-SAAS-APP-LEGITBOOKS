<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create owner role
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'admin']);

        // Create admin user
        $this->admin = Admin::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->admin->assignRole('owner');
    }

    public function test_admin_can_fetch_tenant_details(): void
    {
        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan' => 'plan_starter',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $invoice = Invoice::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$tenant->id}/details");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tenant' => ['id', 'name', 'email', 'status', 'tenant_hash'],
                'subscription' => ['plan', 'plan_name', 'status', 'started_at', 'next_billing_at', 'payment_method'],
                'summary' => ['users_count', 'invoices_count', 'paid_invoices_count', 'due_invoices_count', 'overdue_invoices_count'],
            ]);

        $data = $response->json();
        $this->assertEquals($tenant->id, $data['tenant']['id']);
        $this->assertEquals(1, $data['summary']['users_count']);
        $this->assertEquals(1, $data['summary']['invoices_count']);
    }

    public function test_non_owner_cannot_fetch_tenant_details(): void
    {
        $subadmin = Admin::factory()->create([
            'role' => 'subadmin',
            'is_active' => true,
        ]);
        Role::firstOrCreate(['name' => 'subadmin', 'guard_name' => 'admin']);
        $subadmin->assignRole('subadmin');

        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($subadmin, 'admin')
            ->getJson("/admin/tenants/{$tenant->id}/details");

        $response->assertStatus(403);
    }
}
