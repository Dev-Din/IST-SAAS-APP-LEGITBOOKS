<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that new tenant signup creates a plan_free subscription and redirects to dashboard
     */
    public function test_new_tenant_signup_creates_free_plan_subscription(): void
    {
        $response = $this->post(route('tenant.auth.register.submit'), [
            'company_name' => 'Test Company',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accept_terms' => true,
        ]);

        // Should redirect to dashboard
        $response->assertRedirect(route('tenant.dashboard'));

        // Check tenant was created
        $tenant = Tenant::where('email', 'test@example.com')->first();
        $this->assertNotNull($tenant);

        // Check subscription was created with plan_free
        $subscription = $tenant->subscription;
        $this->assertNotNull($subscription);
        $this->assertEquals('plan_free', $subscription->plan);
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->started_at);
        $this->assertNull($subscription->ends_at);

        // Check user was created and logged in
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_owner);
    }

    /**
     * Test that existing tenant backfill creates plan_free subscription
     */
    public function test_backfill_creates_subscription_for_existing_tenant(): void
    {
        // Create tenant without subscription
        $tenant = Tenant::create([
            'name' => 'Existing Tenant',
            'email' => 'existing@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
        ]);

        // Verify no subscription exists
        $this->assertNull($tenant->subscription);

        // Run seeder
        $this->artisan('db:seed', ['--class' => 'BackfillDefaultSubscriptionsSeeder']);

        // Refresh tenant
        $tenant->refresh();

        // Verify subscription was created
        $subscription = $tenant->subscription;
        $this->assertNotNull($subscription);
        $this->assertEquals('plan_free', $subscription->plan);
        $this->assertEquals('active', $subscription->status);
    }

    /**
     * Test that free plan tenant accessing users page is redirected to billing
     */
    public function test_free_plan_tenant_redirected_from_users_page(): void
    {
        // Create tenant with free plan
        $tenant = Tenant::create([
            'name' => 'Free Tenant',
            'email' => 'free@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan' => 'plan_free',
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => true,
            'permissions' => ['manage_users'],
        ]);

        // Set tenant in session and login
        session(['tenant_id' => $tenant->id]);
        Auth::login($user);

        // Try to access users page
        $response = $this->get(route('tenant.users.index'));

        // Should redirect to billing page with message
        $response->assertRedirect(route('tenant.billing.page'));
        $response->assertSessionHas('info', 'Invite users available on paid plans - Upgrade now to add team members.');
    }

    /**
     * Test upgrade flow simulation updates subscription and allows access to users page
     */
    public function test_upgrade_flow_simulation(): void
    {
        // Create tenant with free plan
        $tenant = Tenant::create([
            'name' => 'Upgrade Tenant',
            'email' => 'upgrade@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan' => 'plan_free',
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_owner' => true,
            'permissions' => ['manage_users'],
        ]);

        // Set tenant in session and login
        session(['tenant_id' => $tenant->id]);
        Auth::login($user);

        // Simulate upgrade (only works in non-production)
        if (config('app.env') !== 'production') {
            $response = $this->post(route('tenant.billing.upgrade'), [
                'plan' => 'starter',
            ]);

            // Should redirect to users page with success message
            $response->assertRedirect(route('tenant.users.index'));
            $response->assertSessionHas('success', 'Upgrade successful — You can now invite users.');

            // Verify subscription was updated
            $subscription->refresh();
            $this->assertEquals('starter', $subscription->plan);
            $this->assertEquals('active', $subscription->status);

            // Now should be able to access users page
            $usersResponse = $this->get(route('tenant.users.index'));
            $usersResponse->assertStatus(200);
        } else {
            $this->markTestSkipped('Upgrade simulation only works in non-production environments');
        }
    }

    /**
     * Test onFreePlan helper method
     */
    public function test_on_free_plan_helper_method(): void
    {
        // Create tenant with free plan
        $tenant = Tenant::create([
            'name' => 'Free Tenant',
            'email' => 'free@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan' => 'plan_free',
            'status' => 'active',
            'started_at' => now(),
        ]);

        $tenant->refresh();
        $this->assertTrue($tenant->onFreePlan());

        // Update to paid plan
        $tenant->subscription->update(['plan' => 'starter']);
        $tenant->refresh();
        $this->assertFalse($tenant->onFreePlan());
    }
}
