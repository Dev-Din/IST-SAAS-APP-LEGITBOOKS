<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'admin']);

        $this->admin = Admin::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->admin->assignRole('owner');

        $this->tenant = Tenant::factory()->create();
    }

    public function test_admin_can_invite_user_and_resend_invite(): void
    {
        Mail::fake();

        // Create invitation
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/tenants/{$this->tenant->id}/users", [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'role_name' => 'admin',
                'permissions' => ['view_invoices', 'manage_invoices'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_invitations', [
            'tenant_id' => $this->tenant->id,
            'email' => 'john@example.com',
            'status' => 'pending',
        ]);

        $invitation = UserInvitation::where('email', 'john@example.com')->first();

        // Resend invitation
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/admin/tenants/{$this->tenant->id}/users/invitations/{$invitation->id}/resend");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_list_tenant_users(): void
    {
        $user1 = User::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Alice']);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Bob']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/users");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users' => [['id', 'name', 'email', 'role_name', 'is_active']],
                'pagination',
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['users']);
    }

    public function test_admin_can_search_users(): void
    {
        User::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Bob', 'email' => 'bob@example.com']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/admin/tenants/{$this->tenant->id}/users?q=alice");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data['users']);
        $this->assertEquals('alice@example.com', $data['users'][0]['email']);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/admin/tenants/{$this->tenant->id}/users/{$user->id}", [
                'first_name' => 'Updated',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'is_owner' => false]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/tenants/{$this->tenant->id}/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_owner(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id, 'is_owner' => true]);

        $response = $this->actingAs($this->admin, 'admin')
            ->deleteJson("/admin/tenants/{$this->tenant->id}/users/{$user->id}");

        $response->assertStatus(422)
            ->assertJson(['error' => 'Cannot delete tenant owner.']);
    }
}
