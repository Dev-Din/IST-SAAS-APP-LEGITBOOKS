<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserInvitation;
use App\Services\Mail\PHPMailerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantUserAdminController extends Controller
{
    protected PHPMailerService $mailer;

    public function __construct(PHPMailerService $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Check if admin has permission
     */
    protected function ensurePermission(): void
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin || (! $admin->hasRole('owner') && ! $admin->hasPermission('tenants.view'))) {
            abort(403, 'You do not have permission to manage tenant users.');
        }
    }

    /**
     * Get paginated users for tenant
     */
    public function index(Request $request, Tenant $tenant)
    {
        $this->ensurePermission();

        $query = User::where('tenant_id', $tenant->id)
            ->with('role');

        // Search filter
        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role') && $request->role) {
            $query->where('role_name', $request->role);
        }

        $users = $query->orderByDesc('is_owner')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get pending invitations
        $invitations = UserInvitation::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role_name' => $user->role_name,
                    'is_owner' => $user->is_owner,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->format('d/m/Y H:i'),
                    'avatar' => $this->getAvatarInitials($user),
                ];
            }),
            'invitations' => $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'email' => $invitation->email,
                    'role_name' => $invitation->role_name,
                    'expires_at' => $invitation->expires_at->format('d/m/Y'),
                    'created_at' => $invitation->created_at->format('d/m/Y H:i'),
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Create/invite user for tenant
     */
    public function store(Request $request, Tenant $tenant)
    {
        $this->ensurePermission();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenant) {
                    return $query->where('tenant_id', $tenant->id);
                }),
            ],
            'role_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:'.implode(',', array_keys(config('tenant_permissions.permissions', []))),
        ]);

        // Check for existing invitation
        $existingInvitation = UserInvitation::where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'error' => 'An active invitation already exists for this email address.',
            ], 422);
        }

        // Generate token
        $token = UserInvitation::generateToken();
        $tempPassword = Str::random(16);
        $tempPasswordHash = Hash::make($tempPassword);

        // Create invitation
        $invitation = UserInvitation::create([
            'tenant_id' => $tenant->id,
            'inviter_user_id' => null, // Admin creating, not a tenant user
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role_name' => $validated['role_name'],
            'permissions' => $validated['permissions'] ?? [],
            'token' => $token,
            'temporary_password_hash' => $tempPasswordHash,
            'expires_at' => now()->addDays(14),
            'status' => 'pending',
        ]);

        // Send invitation email
        $this->sendInvitationEmail($invitation, $tenant, $tempPassword);

        return response()->json([
            'success' => true,
            'message' => 'User invitation sent successfully.',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, Tenant $tenant, User $user)
    {
        $this->ensurePermission();

        // Ensure user belongs to tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenant, $user) {
                    return $query->where('tenant_id', $tenant->id)->where('id', '!=', $user->id);
                }),
            ],
            'role_name' => 'sometimes|string|max:255',
            'permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_name' => $user->role_name,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Delete user
     */
    public function destroy(Tenant $tenant, User $user)
    {
        $this->ensurePermission();

        // Ensure user belongs to tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(404);
        }

        // Prevent deleting owner
        if ($user->is_owner) {
            return response()->json([
                'error' => 'Cannot delete tenant owner.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Resend invitation
     */
    public function resendInvite(Request $request, Tenant $tenant, $invitationId)
    {
        $this->ensurePermission();

        $invitation = UserInvitation::findOrFail($invitationId);

        // Ensure invitation belongs to tenant
        if ($invitation->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'error' => 'Cannot resend a non-pending invitation.',
            ], 422);
        }

        // Generate new token and password if expired
        if ($invitation->isExpired()) {
            $invitation->token = UserInvitation::generateToken();
            $invitation->expires_at = now()->addDays(14);
        }

        $tempPassword = Str::random(16);
        $invitation->temporary_password_hash = Hash::make($tempPassword);
        $invitation->save();

        // Send invitation email
        $this->sendInvitationEmail($invitation, $tenant, $tempPassword);

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully.',
        ]);
    }

    /**
     * Send invitation email
     */
    protected function sendInvitationEmail(UserInvitation $invitation, Tenant $tenant, string $tempPassword): void
    {
        try {
            $acceptUrl = route('invitation.accept', ['token' => $invitation->token]);

            $html = view('emails.users.invitation', [
                'invitation' => $invitation,
                'tenant' => $tenant,
                'acceptUrl' => $acceptUrl,
            ])->render();

            $this->mailer->send([
                'to' => $invitation->email,
                'subject' => "You've been invited to join {$tenant->name}",
                'html' => $html,
                'text' => strip_tags($html),
                'from_name' => $tenant->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send user invitation email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get avatar initials
     */
    protected function getAvatarInitials(User $user): string
    {
        if ($user->first_name && $user->last_name) {
            return strtoupper(substr($user->first_name, 0, 1).substr($user->last_name, 0, 1));
        }
        if ($user->name) {
            $parts = explode(' ', $user->name);
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
            }

            return strtoupper(substr($user->name, 0, 2));
        }

        return strtoupper(substr($user->email, 0, 2));
    }
}
