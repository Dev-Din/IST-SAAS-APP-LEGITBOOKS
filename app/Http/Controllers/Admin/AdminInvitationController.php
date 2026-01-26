<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLog;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdminInvitationController extends Controller
{
    /**
     * Show the invite acceptance form
     */
    public function showAccept(string $token)
    {
        $invitation = AdminInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            return view('admin.invitations.expired', compact('invitation'));
        }

        return view('admin.invitations.accept', compact('invitation'));
    }

    /**
     * Accept the invitation and create admin account
     */
    public function accept(Request $request, string $token)
    {
        $invitation = AdminInvitation::where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            return redirect()->route('admin.invite.accept', $token)
                ->withErrors(['token' => 'This invitation has expired or is no longer valid.']);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.invite.accept', $token)
                ->withErrors($validator)
                ->withInput();
        }

        // Check if admin already exists with this email
        $existingAdmin = Admin::where('email', $invitation->email)->first();
        if ($existingAdmin) {
            return redirect()->route('admin.invite.accept', $token)
                ->withErrors(['email' => 'An admin account with this email already exists.']);
        }

        // Create admin account
        $admin = Admin::create([
            'name' => trim($invitation->first_name.' '.$invitation->last_name),
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'role' => $invitation->role_name === 'owner' ? 'owner' : 'subadmin',
        ]);

        // Assign permissions
        if (! empty($invitation->permissions)) {
            $admin->assignPermissions($invitation->permissions);
        }

        // #region agent log
        $logEntry = [
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'G',
            'location' => 'app/Http/Controllers/Admin/AdminInvitationController.php:accept',
            'message' => 'Before role assignment - checking role_name',
            'data' => [
                'invitation_role_name' => $invitation->role_name,
                'has_role_name' => !empty($invitation->role_name)
            ],
            'timestamp' => round(microtime(true) * 1000)
        ];
        file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
        // #endregion

        // Assign role if specified
        if ($invitation->role_name) {
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'G',
                'location' => 'app/Http/Controllers/Admin/AdminInvitationController.php:accept',
                'message' => 'Checking if role exists before assignment',
                'data' => [
                    'role_name' => $invitation->role_name,
                    'guard_name' => 'admin'
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            
            // Check if role exists, create if it doesn't
            $role = \Spatie\Permission\Models\Role::firstOrCreate(
                ['name' => $invitation->role_name, 'guard_name' => 'admin']
            );
            
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'G',
                'location' => 'app/Http/Controllers/Admin/AdminInvitationController.php:accept',
                'message' => 'Role found or created - assigning to admin',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'was_created' => $role->wasRecentlyCreated
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
            
            $admin->assignRole($role);
            
            // #region agent log
            $logEntry = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'G',
                'location' => 'app/Http/Controllers/Admin/AdminInvitationController.php:accept',
                'message' => 'Role assigned successfully',
                'data' => [
                    'admin_id' => $admin->id,
                    'role_name' => $invitation->role_name
                ],
                'timestamp' => round(microtime(true) * 1000)
            ];
            file_put_contents('/home/nuru/Desktop/SAAS APP LARAVEL/.cursor/debug.log', json_encode($logEntry)."\n", FILE_APPEND);
            // #endregion
        }

        // Mark invitation as accepted
        $invitation->status = 'accepted';
        $invitation->save();

        // Log audit
        AuditLog::record(
            $admin,
            'admin.invite.accepted',
            $invitation,
            [
                'email' => $invitation->email,
                'role_name' => $invitation->role_name,
                'permissions' => $invitation->permissions,
            ]
        );

        // Force password reset on next login (set a flag or use session)
        $request->session()->put('force_password_reset', true);

        return redirect()->route('admin.login')
            ->with('success', 'Your account has been created successfully. Please sign in with your new password.');
    }
}
