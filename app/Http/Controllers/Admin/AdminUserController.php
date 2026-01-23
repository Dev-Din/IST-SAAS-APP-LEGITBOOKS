<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminInvitationRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Admin;
use App\Models\AdminInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Check if the authenticated admin has owner role
     */
    protected function ensureOwner(): void
    {
        abort_unless(
            Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole('owner'),
            403,
            'Only owners can access this resource.'
        );
    }

    /**
     * @deprecated Use ensureOwner() instead
     */
    protected function ensureSuperAdmin(): void
    {
        $this->ensureOwner();
    }

    public function index()
    {
        $this->ensureOwner();
        $admins = Admin::orderByDesc('created_at')->paginate(15);
        $roles = Role::where('guard_name', 'admin')->pluck('name');

        return view('admin.admins.index', compact('admins', 'roles'));
    }

    public function create()
    {
        $this->ensureOwner();
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'name');
        $resources = $this->getSystemResources();
        $pendingInvitations = AdminInvitation::pending()->orderByDesc('created_at')->get();

        return view('admin.admins.create', compact('roles', 'resources', 'pendingInvitations'));
    }

    public function store(StoreAdminInvitationRequest $request)
    {
        $this->ensureOwner();
        $inviter = Auth::guard('admin')->user();
        $data = $request->validated();

        // Check for duplicate pending invitation
        $existing = AdminInvitation::where('email', $data['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return back()->withErrors(['email' => 'An active invitation already exists for this email address.'])->withInput();
        }

        // Generate token and temp password
        $token = AdminInvitation::generateToken();
        $tempPassword = AdminInvitation::generateTempPassword();
        $tempPasswordHash = Hash::make($tempPassword);

        // Create invitation
        $invitation = AdminInvitation::create([
            'inviter_admin_id' => $inviter->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role_name' => $data['role_name'] ?? null,
            'permissions' => $data['permissions'],
            'token' => $token,
            'temp_password_hash' => $tempPasswordHash,
            'expires_at' => now()->addDays(14),
            'status' => 'pending',
        ]);

        // Log audit
        AuditLog::record(
            $inviter,
            'admin.invite.created',
            $invitation,
            [
                'email' => $data['email'],
                'role_name' => $data['role_name'],
                'permissions' => $data['permissions'],
            ]
        );

        // Send invite email
        try {
            $mailService = app(\App\Services\MailService::class);
            $mailService->sendAdminInvite($invitation, $tempPassword);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send admin invite email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);
            // Continue even if email fails - invitation is still created
        }

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin invitation sent successfully. The invitee will receive an email with instructions.');
    }

    public function resendInvite(AdminInvitation $invitation)
    {
        $this->ensureOwner();
        $inviter = Auth::guard('admin')->user();

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['invitation' => 'Cannot resend a non-pending invitation.']);
        }

        if ($invitation->isExpired()) {
            // Regenerate token and extend expiry
            $invitation->token = AdminInvitation::generateToken();
            $invitation->expires_at = now()->addDays(14);
            $invitation->save();
        }

        // Generate new temp password
        $tempPassword = AdminInvitation::generateTempPassword();
        $invitation->temp_password_hash = Hash::make($tempPassword);
        $invitation->save();

        // Log audit
        AuditLog::record(
            $inviter,
            'admin.invite.resent',
            $invitation,
            ['email' => $invitation->email]
        );

        // Resend email
        try {
            $mailService = app(\App\Services\MailService::class);
            $mailService->sendAdminInvite($invitation, $tempPassword);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to resend admin invite email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send email. Please try again.']);
        }

        return back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Get system resources for permission matrix
     */
    protected function getSystemResources(): array
    {
        return [
            'tenants' => 'Tenants',
            'admins' => 'Admins',
            'users' => 'Users',
            'invoices' => 'Invoices',
            'payments' => 'Payments',
            'billing' => 'Billing',
            'reports' => 'Reports',
            'settings' => 'Settings',
        ];
    }

    public function edit(Admin $admin)
    {
        $this->ensureOwner();
        $roles = Role::where('guard_name', 'admin')->pluck('name', 'name');

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(UpdateAdminUserRequest $request, Admin $admin)
    {
        $this->ensureOwner();
        $data = $request->validated();

        $admin->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $admin->update(['password' => Hash::make($data['password'])]);
        }

        $admin->syncRoles([$data['role']]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin user updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        $this->ensureOwner();
        abort_if(Auth::guard('admin')->id() === $admin->id, 403, 'You cannot delete yourself.');
        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
