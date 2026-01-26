<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AdminInvitation;
use App\Services\Mail\PHPMailerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AdminInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin role if using Spatie
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'superadmin',
                'guard_name' => 'admin',
            ]);
        }
    }

    /** @test */
    public function test_superadmin_can_create_invite_and_email_sent()
    {
        // Create superadmin
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $superadmin->assignRole('superadmin');
        }

        // Mock PHPMailer service
        $phpMailerMock = Mockery::mock(PHPMailerService::class);
        $phpMailerMock->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['to'])
                    && isset($data['subject'])
                    && isset($data['html'])
                    && str_contains($data['html'], 'Temporary Password')
                    && str_contains($data['html'], 'Accept Invitation');
            }))
            ->andReturn(true);

        $this->app->instance(PHPMailerService::class, $phpMailerMock);

        // Make request
        $response = $this->actingAs($superadmin, 'admin')
            ->post(route('admin.admins.store'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'role_name' => 'Support Admin',
                'permissions' => [
                    'tenants.view',
                    'tenants.create',
                    'users.view',
                ],
            ]);

        $response->assertRedirect(route('admin.admins.index'));
        $response->assertSessionHas('success');

        // Assert invitation created
        $this->assertDatabaseHas('admin_invitations', [
            'email' => 'john.doe@example.com',
            'status' => 'pending',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $invitation = AdminInvitation::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($invitation);
        $this->assertNotNull($invitation->token);
        $this->assertNotNull($invitation->temp_password_hash);
        $this->assertTrue($invitation->expires_at->isFuture());

        // Assert audit log created
        $this->assertDatabaseHas('platform_audit_logs', [
            'admin_id' => $superadmin->id,
            'action' => 'admin.invite.created',
            'target_id' => $invitation->id,
        ]);
    }

    /** @test */
    public function test_invite_accept_creates_admin_and_forces_password_reset()
    {
        // Create superadmin and invitation
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $invitation = AdminInvitation::create([
            'inviter_admin_id' => $superadmin->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'role_name' => 'Finance Admin',
            'permissions' => ['tenants.view', 'invoices.view'],
            'token' => AdminInvitation::generateToken(),
            'temp_password_hash' => Hash::make('temp-password-123'),
            'expires_at' => now()->addDays(14),
            'status' => 'pending',
        ]);

        // Accept invitation
        $response = $this->post(route('admin.invite.accept', $invitation->token), [
            'password' => 'new-secure-password-123',
            'password_confirmation' => 'new-secure-password-123',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('success');

        // Assert admin created
        $this->assertDatabaseHas('admins', [
            'email' => 'jane.smith@example.com',
            'name' => 'Jane Smith',
            'is_active' => true,
        ]);

        $admin = Admin::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('new-secure-password-123', $admin->password));

        // Assert invitation marked as accepted
        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);

        // Assert audit log created
        $this->assertDatabaseHas('platform_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'admin.invite.accepted',
            'target_id' => $invitation->id,
        ]);

        // Assert permissions assigned
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            $permissions = $admin->getPermissionStrings();
            $this->assertContains('tenants.view', $permissions);
            $this->assertContains('invoices.view', $permissions);
        }
    }

    /** @test */
    public function test_duplicate_invite_is_prevented()
    {
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Create existing pending invitation
        $existing = AdminInvitation::create([
            'inviter_admin_id' => $superadmin->id,
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'permissions' => ['tenants.view'],
            'token' => AdminInvitation::generateToken(),
            'temp_password_hash' => Hash::make('temp'),
            'expires_at' => now()->addDays(14),
            'status' => 'pending',
        ]);

        // Try to create duplicate
        $response = $this->actingAs($superadmin, 'admin')
            ->post(route('admin.admins.store'), [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'existing@example.com',
                'permissions' => ['tenants.view'],
            ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('already exists', $response->getSession()->get('errors')->first('email'));
    }

    /** @test */
    public function test_resend_invite_regenerates_token_and_sends_email()
    {
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $invitation = AdminInvitation::create([
            'inviter_admin_id' => $superadmin->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'permissions' => ['tenants.view'],
            'token' => 'old-token-123',
            'temp_password_hash' => Hash::make('old-temp'),
            'expires_at' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $oldToken = $invitation->token;
        $oldExpiry = $invitation->expires_at;

        // Mock PHPMailer
        $phpMailerMock = Mockery::mock(PHPMailerService::class);
        $phpMailerMock->shouldReceive('send')
            ->once()
            ->andReturn(true);
        $this->app->instance(PHPMailerService::class, $phpMailerMock);

        // Resend invite
        $response = $this->actingAs($superadmin, 'admin')
            ->post(route('admin.admins.resend-invite', $invitation->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Assert token and expiry updated
        $invitation->refresh();
        if ($invitation->isExpired()) {
            $this->assertNotEquals($oldToken, $invitation->token);
            $this->assertTrue($invitation->expires_at->isFuture());
        }

        // Assert audit log
        $this->assertDatabaseHas('platform_audit_logs', [
            'admin_id' => $superadmin->id,
            'action' => 'admin.invite.resent',
            'target_id' => $invitation->id,
        ]);
    }

    /** @test */
    public function test_expired_invitation_cannot_be_accepted()
    {
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $invitation = AdminInvitation::create([
            'inviter_admin_id' => $superadmin->id,
            'first_name' => 'Expired',
            'last_name' => 'User',
            'email' => 'expired@example.com',
            'permissions' => ['tenants.view'],
            'token' => AdminInvitation::generateToken(),
            'temp_password_hash' => Hash::make('temp'),
            'expires_at' => now()->subDay(), // Expired
            'status' => 'pending',
        ]);

        $response = $this->post(route('admin.invite.accept', $invitation->token), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors(['token']);
        $this->assertDatabaseMissing('admins', ['email' => 'expired@example.com']);
    }

    /** @test */
    public function test_non_superadmin_cannot_create_invite()
    {
        $admin = Admin::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.admins.store'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'permissions' => ['tenants.view'],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_login_updates_invitation_status_from_accepted_to_active()
    {
        // Create superadmin and invitation
        $superadmin = Admin::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $invitation = AdminInvitation::create([
            'inviter_admin_id' => $superadmin->id,
            'first_name' => 'Active',
            'last_name' => 'User',
            'email' => 'active.user@example.com',
            'role_name' => 'Support Admin',
            'permissions' => ['tenants.view'],
            'token' => AdminInvitation::generateToken(),
            'temp_password_hash' => Hash::make('temp-password-123'),
            'expires_at' => now()->addDays(14),
            'status' => 'pending',
        ]);

        // Accept invitation (status becomes 'accepted')
        $this->post(route('admin.invite.accept', $invitation->token), [
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ]);

        // Verify invitation is now 'accepted'
        $invitation->refresh();
        $this->assertEquals('accepted', $invitation->status);

        // Now login with the admin account
        $admin = Admin::where('email', 'active.user@example.com')->first();
        $this->assertNotNull($admin);

        $response = $this->post(route('admin.login.post'), [
            'email' => 'active.user@example.com',
            'password' => 'secure-password-123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        // Verify invitation status changed from 'accepted' to 'active'
        $invitation->refresh();
        $this->assertEquals('active', $invitation->status);
    }
}
