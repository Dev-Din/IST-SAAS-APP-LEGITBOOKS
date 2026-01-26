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
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:create',
            'message' => 'Starting create method - fetching pending invitations',
            'data' => [],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        // Get pending invitations that don't have an existing admin account
        $allPendingCount = AdminInvitation::pending()->count();
        $adminEmails = Admin::select('email')->pluck('email')->toArray();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:create',
            'message' => 'Before filtering - pending count and admin emails',
            'data' => [
                'all_pending_count' => $allPendingCount,
                'admin_emails_count' => count($adminEmails),
                'admin_emails_sample' => array_slice($adminEmails, 0, 5)
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        $pendingInvitations = AdminInvitation::pending()
            ->whereNotIn('email', Admin::select('email'))
            ->orderByDesc('created_at')
            ->get();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:create',
            'message' => 'After filtering - filtered invitations count',
            'data' => [
                'filtered_count' => $pendingInvitations->count(),
                'filtered_emails' => $pendingInvitations->pluck('email')->toArray()
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        return view('admin.admins.create', compact('roles', 'resources', 'pendingInvitations'));
    }

    public function store(StoreAdminInvitationRequest $request)
    {
        $this->ensureOwner();
        $inviter = Auth::guard('admin')->user();
        $data = $request->validated();

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:store',
            'message' => 'Starting store method - checking for existing admin',
            'data' => [
                'email' => $data['email'],
                'inviter_id' => $inviter->id
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Check if admin already exists with this email
        $existingAdmin = Admin::where('email', $data['email'])->first();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:store',
            'message' => 'Admin existence check result',
            'data' => [
                'email' => $data['email'],
                'admin_exists' => $existingAdmin !== null,
                'admin_id' => $existingAdmin ? $existingAdmin->id : null
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        if ($existingAdmin) {
            return back()->withErrors(['email' => 'An admin account with this email already exists.'])->withInput();
        }

        // Check for duplicate pending invitation
        $existing = AdminInvitation::where('email', $data['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:store',
            'message' => 'Pending invitation check result',
            'data' => [
                'email' => $data['email'],
                'invitation_exists' => $existing !== null,
                'invitation_id' => $existing ? $existing->id : null,
                'invitation_status' => $existing ? $existing->status : null
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

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
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'A',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:resendInvite',
                'message' => 'Starting email resend',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'email' => $invitation->email,
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            $mailService = app(\App\Services\MailService::class);
            
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'A',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:resendInvite',
                'message' => 'MailService instance created, calling sendAdminInvite',
                'data' => [],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            $result = $mailService->sendAdminInvite($invitation, $tempPassword);
            
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'A',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:resendInvite',
                'message' => 'sendAdminInvite returned',
                'data' => [
                    'result' => $result,
                    'result_type' => gettype($result),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            if (!$result) {
                // #region agent log
                $logEntry = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'A',
                    'location' => 'app/Http/Controllers/Admin/AdminUserController.php:resendInvite',
                    'message' => 'sendAdminInvite returned false',
                    'data' => [],
                    'timestamp' => round(microtime(true) * 1000)
                ];
                file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
                // #endregion

                return back()->withErrors(['email' => 'Failed to send email. Please try again.']);
            }
        } catch (\Exception $e) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'A',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:resendInvite',
                'message' => 'Exception caught in resendInvite',
                'data' => [
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion

            \Illuminate\Support\Facades\Log::error('Failed to resend admin invite email', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => 'Failed to send email. Please try again.']);
        }

        return back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Cancel an admin invitation
     */
    public function cancelInvitation(AdminInvitation $invitation)
    {
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:cancelInvitation',
            'message' => 'Cancel invitation method entry',
            'data' => [
                'invitation_id' => $invitation->id,
                'invitation_email' => $invitation->email,
                'invitation_status' => $invitation->status
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        $this->ensureOwner();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'E',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:cancelInvitation',
            'message' => 'After permission check - validating status',
            'data' => [
                'invitation_status' => $invitation->status,
                'is_pending' => $invitation->status === 'pending'
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        if ($invitation->status !== 'pending') {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'E',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:cancelInvitation',
                'message' => 'Validation failed - not pending',
                'data' => [
                    'invitation_status' => $invitation->status
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            return back()->withErrors(['invitation' => 'Only pending invitations can be cancelled.']);
        }
        
        $invitation->status = 'cancelled';
        $saveResult = $invitation->save();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:cancelInvitation',
            'message' => 'Invitation cancelled',
            'data' => [
                'invitation_id' => $invitation->id,
                'new_status' => $invitation->status,
                'save_result' => $saveResult
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        // Log audit
        AuditLog::record(
            Auth::guard('admin')->user(),
            'admin.invite.cancelled',
            $invitation,
            ['email' => $invitation->email]
        );
        
        return back()->with('success', 'Invitation cancelled successfully.');
    }

    /**
     * Delete an admin invitation
     */
    public function destroyInvitation(AdminInvitation $invitation)
    {
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroyInvitation',
            'message' => 'Delete invitation method entry',
            'data' => [
                'invitation_id' => $invitation->id,
                'invitation_email' => $invitation->email,
                'invitation_status' => $invitation->status
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        $this->ensureOwner();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'F',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroyInvitation',
            'message' => 'After permission check - validating status',
            'data' => [
                'invitation_status' => $invitation->status,
                'is_deletable' => !in_array($invitation->status, ['accepted', 'active'])
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        if (in_array($invitation->status, ['accepted', 'active'])) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'F',
                'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroyInvitation',
                'message' => 'Validation failed - cannot delete accepted/active',
                'data' => [
                    'invitation_status' => $invitation->status
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            return back()->withErrors(['invitation' => 'Cannot delete accepted or active invitations.']);
        }
        
        $email = $invitation->email;
        $deleteResult = $invitation->delete();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroyInvitation',
            'message' => 'Invitation deleted',
            'data' => [
                'invitation_id' => $invitation->id,
                'invitation_email' => $email,
                'delete_result' => $deleteResult
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        // Log audit
        AuditLog::record(
            Auth::guard('admin')->user(),
            'admin.invite.deleted',
            null,
            ['email' => $email]
        );
        
        return back()->with('success', 'Invitation deleted successfully.');
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
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroy',
            'message' => 'Starting destroy method - finding associated invitations',
            'data' => [
                'admin_id' => $admin->id,
                'admin_email' => $admin->email
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        // Cancel all associated invitations
        $invitations = AdminInvitation::where('email', $admin->email)->get();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroy',
            'message' => 'Found associated invitations',
            'data' => [
                'admin_email' => $admin->email,
                'invitations_count' => $invitations->count(),
                'invitations' => $invitations->map(function($inv) {
                    return [
                        'id' => $inv->id,
                        'status' => $inv->status,
                        'email' => $inv->email
                    ];
                })->toArray()
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        $cancelledCount = 0;
        foreach ($invitations as $invitation) {
            if (in_array($invitation->status, ['pending', 'accepted', 'active'])) {
                $oldStatus = $invitation->status;
                $invitation->status = 'cancelled';
                $saveResult = $invitation->save();
                
                // #region agent log
                $logEntry = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'A',
                    'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroy',
                    'message' => 'Cancelled invitation',
                    'data' => [
                        'invitation_id' => $invitation->id,
                        'old_status' => $oldStatus,
                        'new_status' => $invitation->status,
                        'save_result' => $saveResult
                    ],
                    'timestamp' => round(microtime(true) * 1000)
                ];
                file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
                // #endregion
                
                if ($saveResult) {
                    $cancelledCount++;
                }
            }
        }
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroy',
            'message' => 'Before deleting admin',
            'data' => [
                'admin_id' => $admin->id,
                'cancelled_invitations_count' => $cancelledCount
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion
        
        $admin->delete();
        
        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A',
            'location' => 'app/Http/Controllers/Admin/AdminUserController.php:destroy',
            'message' => 'Admin deleted successfully',
            'data' => [
                'admin_id' => $admin->id,
                'cancelled_invitations_count' => $cancelledCount
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        return redirect()->route('admin.admins.index')->with('success', 'Admin user deleted successfully.');
    }
}
