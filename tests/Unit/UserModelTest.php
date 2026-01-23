<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->createTestTenant();
    }

    public function test_user_has_tenant_relationship(): void
    {
        $user = $this->createTestUser();

        $this->assertInstanceOf(Tenant::class, $user->tenant);
        $this->assertEquals($this->tenant->id, $user->tenant->id);
    }

    public function test_has_permission_returns_true_for_owner(): void
    {
        $user = $this->createTestUser(['is_owner' => true, 'permissions' => []]);

        $this->assertTrue($user->hasPermission('manage_invoices'));
        $this->assertTrue($user->hasPermission('any_permission'));
    }

    public function test_has_permission_returns_true_when_permission_exists(): void
    {
        $user = $this->createTestUser([
            'is_owner' => false,
            'permissions' => ['manage_invoices', 'view_invoices'],
        ]);

        $this->assertTrue($user->hasPermission('manage_invoices'));
        $this->assertTrue($user->hasPermission('view_invoices'));
    }

    public function test_has_permission_returns_false_when_permission_not_exists(): void
    {
        $user = $this->createTestUser([
            'is_owner' => false,
            'permissions' => ['view_invoices'],
        ]);

        $this->assertFalse($user->hasPermission('manage_invoices'));
    }

    public function test_has_any_permission_returns_true_for_owner(): void
    {
        $user = $this->createTestUser(['is_owner' => true, 'permissions' => []]);

        $this->assertTrue($user->hasAnyPermission(['manage_invoices', 'view_invoices']));
    }

    public function test_has_any_permission_returns_true_when_any_permission_exists(): void
    {
        $user = $this->createTestUser([
            'is_owner' => false,
            'permissions' => ['view_invoices'],
        ]);

        $this->assertTrue($user->hasAnyPermission(['manage_invoices', 'view_invoices']));
    }

    public function test_has_any_permission_returns_false_when_no_permission_exists(): void
    {
        $user = $this->createTestUser([
            'is_owner' => false,
            'permissions' => ['other_permission'],
        ]);

        $this->assertFalse($user->hasAnyPermission(['manage_invoices', 'view_invoices']));
    }

    public function test_get_full_name_returns_first_and_last_name_when_available(): void
    {
        $user = $this->createTestUser([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_get_full_name_returns_name_when_first_last_not_available(): void
    {
        $user = $this->createTestUser([
            'name' => 'John Doe',
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertEquals('John Doe', $user->full_name);
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

    protected function createTestUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => false,
            'permissions' => [],
        ], $attributes));
    }
}
