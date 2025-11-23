<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Mail\PHPMailerService;
use App\Services\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantUserController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PHPMailerService $mailer
    ) {}

    /**
     * Display a listing of users and invitations
     */
    public function index()
    {
        $tenant = $this->tenantContext->getTenant();
        
        // Get users from users table only (users who have accepted their invitation)
        // Order: Owner first, then others by first name (or created_at as fallback)
        $users = User::where('tenant_id', $tenant->id)
            ->orderByDesc('is_owner')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get only pending, non-expired invitations from user_invitations table
        // Since we delete invitations after acceptance, we only need to show pending ones
        $invitations = UserInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Mark any expired invitations (for cleanup)
        $expiredInvitations = UserInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();
        
        foreach ($expiredInvitations as $invitation) {
            $invitation->markAsExpired();
        }
        
        $permissions = config('tenant_permissions.permissions', []);
        
        return view('tenant.users.index', compact('users', 'invitations', 'permissions'));
    }

    /**
     * Show the form for inviting a new user
     */
    public function create()
    {
        $permissions = config('tenant_permissions.permissions', []);
        $permissionGroups = config('tenant_permissions.groups', []);
        
        return view('tenant.users.invite', compact('permissions', 'permissionGroups'));
    }

    /**
     * Store a newly created invitation
     */
    public function store(Request $request)
    {
        $tenant = $this->tenantContext->getTenant();
        $user = Auth::user();
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'role_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(config('tenant_permissions.permissions', []))),
        ]);
        
        $email = $validated['email'];
        
        // Check if this email belongs to the tenant owner in this same tenant
        $ownerExistsInThisTenant = User::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->where('is_owner', true)
            ->exists();
        
        if ($ownerExistsInThisTenant) {
            return back()->withErrors([
                'email' => 'This email belongs to the tenant account owner and cannot be invited as a user in this tenant.',
            ])->withInput();
        }
        
        // Check if user already exists in this tenant
        $existingUser = User::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->first();
        
        if ($existingUser) {
            return back()->withErrors([
                'email' => 'A user with this email already exists in this tenant.',
            ])->withInput();
        }
        
        // Check if pending or non-expired invitation exists for this tenant+email
        $existingInvitation = UserInvitation::where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        
        if ($existingInvitation) {
            return back()->withErrors([
                'email' => 'An invitation for this email already exists. You can resend it from the User Management page.',
            ])->withInput();
        }
        
        // Generate secure token
        $token = UserInvitation::generateToken();
        
        // Create invitation with try/catch as safeguard for race conditions
        try {
            $invitation = UserInvitation::create([
                'tenant_id' => $tenant->id,
                'inviter_user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'role_name' => $validated['role_name'],
                'permissions' => $validated['permissions'] ?? [],
                'token' => $token,
                'expires_at' => now()->addDays(14),
                'status' => 'pending',
            ]);
        } catch (QueryException $e) {
            // Handle unique constraint violation (race condition safeguard)
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'user_invitations_tenant_email_unique')) {
                return back()->withErrors([
                    'email' => 'An invitation for this email already exists in this tenant. Please check the User Management page.',
                ])->withInput();
            }
            // Re-throw if it's a different database error
            throw $e;
        }
        
        // Send invitation email
        $this->sendInvitationEmail($invitation, $tenant);
        
        return redirect()->route('tenant.users.index')
            ->with('success', 'Invitation sent successfully!');
    }

    /**
     * Show the form for editing a user
     */
    public function edit(User $user)
    {
        $tenant = $this->tenantContext->getTenant();
        
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Prevent editing account owner
        if ($user->is_owner) {
            return redirect()->route('tenant.users.index')
                ->withErrors(['error' => 'Account owner cannot be edited through this interface.']);
        }
        
        $permissions = config('tenant_permissions.permissions', []);
        $permissionGroups = config('tenant_permissions.groups', []);
        
        return view('tenant.users.edit', compact('user', 'permissions', 'permissionGroups'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $tenant = $this->tenantContext->getTenant();
        
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Prevent editing account owner's permissions and role
        if ($user->is_owner) {
            return back()->withErrors(['error' => 'Account owner permissions and role cannot be modified.'])
                ->withInput();
        }
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(config('tenant_permissions.permissions', []))),
        ]);
        
        // Only update non-owner fields - never update permissions/role for owners
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role_name' => $validated['role_name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);
        
        return redirect()->route('tenant.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Activate a user
     */
    public function activate(User $user)
    {
        $tenant = $this->tenantContext->getTenant();
        
        if ($user->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        $user->update(['is_active' => true]);
        
        return back()->with('success', 'User activated successfully!');
    }

    /**
     * Deactivate a user
     */
    public function deactivate(User $user)
    {
        $tenant = $this->tenantContext->getTenant();
        $currentUser = Auth::user();
        
        if ($user->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Prevent deactivating account owner
        if ($user->is_owner) {
            return back()->withErrors(['error' => 'Account owner cannot be deactivated.']);
        }
        
        // Prevent deactivating yourself
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }
        
        $user->update(['is_active' => false]);
        
        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'The user account has been deactivated and can no longer access LegitBooks.');
    }

    /**
     * Delete a user account
     */
    public function destroy(User $user)
    {
        $tenant = $this->tenantContext->getTenant();
        $currentUser = Auth::user();
        
        // Ensure user belongs to current tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Prevent deleting account owner
        if ($user->is_owner) {
            return redirect()
                ->route('tenant.users.index')
                ->withErrors(['error' => 'You cannot delete the account owner.']);
        }
        
        // Prevent deleting yourself
        if ($user->id === $currentUser->id) {
            return redirect()
                ->route('tenant.users.index')
                ->withErrors(['error' => 'You cannot delete your own account.']);
        }
        
        // Delete the user
        $user->delete();
        
        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'The user account has been deleted successfully.');
    }

    /**
     * Delete an invitation
     */
    public function destroyInvitation(UserInvitation $invitation)
    {
        $tenant = $this->tenantContext->getTenant();
        
        // Ensure invitation belongs to current tenant
        if ($invitation->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Delete the invitation
        $invitation->delete();
        
        return redirect()
            ->route('tenant.users.index')
            ->with('status', 'The invitation has been deleted successfully.');
    }

    /**
     * Resend invitation email
     */
    public function resendInvitation(UserInvitation $invitation)
    {
        $tenant = $this->tenantContext->getTenant();
        
        if ($invitation->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        if ($invitation->status !== 'pending') {
            return back()->withErrors(['error' => 'This invitation is no longer pending.']);
        }
        
        if ($invitation->isExpired()) {
            return back()->withErrors(['error' => 'This invitation has expired. Please send a new invitation.']);
        }
        
        $this->sendInvitationEmail($invitation, $tenant);
        
        return back()->with('success', 'Invitation resent successfully!');
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation(UserInvitation $invitation)
    {
        $tenant = $this->tenantContext->getTenant();
        
        if ($invitation->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }
        
        if ($invitation->status !== 'pending') {
            return back()->withErrors(['error' => 'This invitation cannot be cancelled.']);
        }
        
        $invitation->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Invitation cancelled successfully!');
    }

    /**
     * Send invitation email via PHPMailer
     */
    protected function sendInvitationEmail(UserInvitation $invitation, $tenant): void
    {
        try {
            $acceptUrl = route('invitation.accept', $invitation->token);
            
            $html = view('emails.users.invitation', [
                'tenant' => $tenant,
                'invitation' => $invitation,
                'acceptUrl' => $acceptUrl,
            ])->render();
            
            $this->mailer->send([
                'to' => $invitation->email,
                'subject' => "You've Been Invited to Join {$tenant->name} on LegitBooks",
                'html' => $html,
                'reply_to' => $tenant->email ?? 'nurudiin222@gmail.com',
                'from_name' => "{$tenant->name} via LegitBooks",
            ]);
            
            Log::info('User invitation email sent', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invitation email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
